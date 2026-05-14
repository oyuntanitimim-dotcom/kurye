<?php

declare(strict_types=1);

namespace App\Modules\Orders\Services;

use App\Enums\OrderStatus;
use App\Infrastructure\Geo\Contracts\CourierGeoLocatorInterface;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderDispatchDecision;
use App\Modules\Restaurants\Models\Restaurant;
use App\Support\Geo\Haversine;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

class AutoDispatchService
{
    private const ACTIVE_DELIVERY_STATUSES = [
        'courier_assigned',
        'courier_accepted',
        'picked_up',
        'on_the_way',
    ];

    public function __construct(
        private readonly OrderStateService $orderStateService,
        private readonly CourierGeoLocatorInterface $geoLocator,
    ) {}

    /**
     * @return array{ok: bool, message: string, courier_id: ?int}
     */
    public function dispatchOrder(Order $order, string $trigger, ?int $createdByUserId): array
    {
        return DB::transaction(function () use ($order, $trigger, $createdByUserId): array {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== OrderStatus::Ready->value) {
                $this->logDecision($locked, $trigger, $createdByUserId, null, [
                    'error' => 'order_not_ready',
                    'status' => $locked->status,
                ]);

                return ['ok' => false, 'message' => 'Sipariş hazır değil.', 'courier_id' => null];
            }

            if ($locked->courier_id !== null) {
                $this->logDecision($locked, $trigger, $createdByUserId, null, [
                    'error' => 'courier_already_set',
                ]);

                return ['ok' => false, 'message' => 'Kurye zaten atanmış.', 'courier_id' => null];
            }

            if (in_array($trigger, ['auto_ready', 'restaurant_courier_request'], true)
                && $locked->restaurant_courier_requested_at === null) {
                $this->logDecision($locked, $trigger, $createdByUserId, null, [
                    'error' => 'restaurant_courier_not_requested',
                ]);

                return ['ok' => false, 'message' => 'Restoran henüz kurye çağırmadı.', 'courier_id' => null];
            }

            $firm = Firm::query()->whereKey($locked->firm_id)->lockForUpdate()->first();
            if ($firm === null) {
                return ['ok' => false, 'message' => 'Kurye şirketi bulunamadı.', 'courier_id' => null];
            }

            $settings = $firm->mergedOperationSettings();
            if (in_array($trigger, ['auto_ready', 'restaurant_courier_request'], true) && empty($settings['auto_dispatch_enabled'])) {
                $this->logDecision($locked, $trigger, $createdByUserId, null, [
                    'error' => 'auto_dispatch_disabled',
                ]);

                return ['ok' => false, 'message' => 'Otomatik atama kapalı.', 'courier_id' => null];
            }

            $restaurant = Restaurant::query()->whereKey($locked->restaurant_id)->first();
            if ($restaurant === null
                || $restaurant->latitude === null
                || $restaurant->longitude === null) {
                $this->logDecision($locked, $trigger, $createdByUserId, null, [
                    'error' => 'restaurant_coordinates_missing',
                ]);

                return ['ok' => false, 'message' => 'Firma konumu eksik.', 'courier_id' => null];
            }

            $rlat = (float) $restaurant->latitude;
            $rlng = (float) $restaurant->longitude;

            $maxAge = max(1, (int) $settings['location_max_age_minutes']);
            $wDist = (float) $settings['dispatch_weight_distance'];
            $wActive = (float) $settings['dispatch_weight_active_orders'];
            $wStale = (float) $settings['dispatch_weight_stale'];

            $pool = $this->loadCourierPool((int) $locked->firm_id, $rlat, $rlng, preferGeoNear: true);
            $scoredResult = $this->scoreCourierPool($locked, $pool['couriers'], $rlat, $rlng, $maxAge, $wDist, $wActive, $wStale);
            $geoPrefetchUsed = $pool['from_geo_subset'];
            $dispatchGeoFallback = false;

            if ($scoredResult['scored'] === [] && $pool['from_geo_subset']) {
                $pool = $this->loadCourierPool((int) $locked->firm_id, $rlat, $rlng, preferGeoNear: false);
                $scoredResult = $this->scoreCourierPool($locked, $pool['couriers'], $rlat, $rlng, $maxAge, $wDist, $wActive, $wStale);
                $dispatchGeoFallback = true;
                $scoredResult['candidates'][] = [
                    'note' => 'dispatch_geo_prefilter_retry_full_pool',
                ];
            }

            $candidates = $scoredResult['candidates'];
            $scored = $scoredResult['scored'];
            $loadFallbackUsed = false;

            if ($scored === [] && $pool['couriers']->isNotEmpty()) {
                $fb = $this->scoreCourierPoolLoadOnlyFallback($locked, $pool['couriers'], $wActive);
                $candidates = array_merge(
                    $candidates,
                    [['note' => 'dispatch_fallback_no_usable_gps', 'pool_size' => $pool['couriers']->count()]],
                    $fb['candidates'],
                );
                $scored = $fb['scored'];
                $loadFallbackUsed = true;
            }

            if ($scored === []) {
                $this->logDecision($locked, $trigger, $createdByUserId, null, [
                    'candidates' => $candidates,
                    'error' => 'no_eligible_courier',
                    'dispatch_geo_prefilter_used' => $geoPrefetchUsed,
                    'dispatch_geo_fallback_full_pool' => $dispatchGeoFallback,
                ]);

                return ['ok' => false, 'message' => 'Uygun kurye yok (konum veya kapasite).', 'courier_id' => null];
            }

            usort($scored, function (array $a, array $b): int {
                $byScore = ($a['score'] <=> $b['score']);

                return $byScore !== 0 ? $byScore : (($a['courier_id'] ?? 0) <=> ($b['courier_id'] ?? 0));
            });
            $best = $scored[0];
            $chosenId = (int) $best['courier_id'];

            $locked->courier_id = $chosenId;
            $locked->save();

            $fresh = $locked->fresh();
            if ($fresh !== null) {
                $this->orderStateService->transition($fresh, OrderStatus::CourierAssigned, [
                    'auto_dispatch' => true,
                    'trigger' => $trigger,
                ]);
            }

            $this->logDecision($locked, $trigger, $createdByUserId, $chosenId, [
                'candidates' => $candidates,
                'chosen' => $best,
                'dispatch_geo_pool' => $pool['pool_kind'],
                'dispatch_geo_fallback_full_pool' => $dispatchGeoFallback,
                'dispatch_load_fallback' => $loadFallbackUsed,
            ]);

            return ['ok' => true, 'message' => 'Kurye otomatik atandı.', 'courier_id' => $chosenId];
        });
    }

    /**
     * @return array{couriers: EloquentCollection<int, Courier>, from_geo_subset: bool, pool_kind: string}
     */
    private function loadCourierPool(int $firmId, float $restaurantLat, float $restaurantLng, bool $preferGeoNear): array
    {
        $base = Courier::query()
            ->where('firm_id', $firmId)
            ->where('status', 'active');

        if (! $preferGeoNear || ! config('courier.geo_redis_enabled', false)) {
            return [
                'couriers' => $base->clone()->with('location')->get(),
                'from_geo_subset' => false,
                'pool_kind' => 'full',
            ];
        }

        $radius = (float) config('courier.dispatch_geo_prefilter_radius_km', 35.0);
        $limit = (int) config('courier.dispatch_geo_prefilter_limit', 120);

        $nearIds = $this->geoLocator->courierIdsNear($firmId, $restaurantLat, $restaurantLng, $radius, $limit);

        if ($nearIds === []) {
            return [
                'couriers' => $base->clone()->with('location')->get(),
                'from_geo_subset' => false,
                'pool_kind' => 'full_geo_empty',
            ];
        }

        $subset = $base->clone()->whereIn('id', $nearIds)->with('location')->get();

        if ($subset->isEmpty()) {
            return [
                'couriers' => $base->clone()->with('location')->get(),
                'from_geo_subset' => false,
                'pool_kind' => 'full_geo_ids_missing_in_db',
            ];
        }

        return [
            'couriers' => $subset,
            'from_geo_subset' => true,
            'pool_kind' => 'redis_near',
        ];
    }

    /**
     * @return array{candidates: list<array<string, mixed>>, scored: list<array<string, mixed>>}
     */
    private function scoreCourierPool(
        Order $locked,
        EloquentCollection $couriers,
        float $rlat,
        float $rlng,
        int $maxAge,
        float $wDist,
        float $wActive,
        float $wStale,
    ): array {
        $courierIds = $couriers->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $activeCounts = $this->activeDeliveryCountsByCourier((int) $locked->firm_id, $courierIds);

        $candidates = [];
        foreach ($couriers as $c) {
            $loc = $c->location;
            if ($loc === null || $loc->latitude === null || $loc->longitude === null) {
                $candidates[] = [
                    'courier_id' => $c->id,
                    'excluded' => true,
                    'reason' => 'no_location',
                ];

                continue;
            }

            $updatedAt = $loc->updated_at ?? now();
            $ageMinutes = now()->diffInMinutes($updatedAt, true);

            if ($ageMinutes > $maxAge * 2) {
                $candidates[] = [
                    'courier_id' => $c->id,
                    'excluded' => true,
                    'reason' => 'location_too_stale',
                    'age_minutes' => $ageMinutes,
                ];

                continue;
            }

            $clat = (float) $loc->latitude;
            $clng = (float) $loc->longitude;
            $distanceKm = Haversine::distanceKm($rlat, $rlng, $clat, $clng);
            $activeCount = $activeCounts[$c->id] ?? 0;
            $stalePenalty = max(0.0, $ageMinutes - $maxAge);
            $score = $wDist * $distanceKm
                + $wActive * $activeCount
                + $wStale * $stalePenalty;

            $candidates[] = [
                'courier_id' => $c->id,
                'excluded' => false,
                'distance_km' => round($distanceKm, 4),
                'active_orders' => $activeCount,
                'location_age_minutes' => $ageMinutes,
                'score' => round($score, 6),
            ];
        }

        $scored = array_values(array_filter($candidates, fn (array $row): bool => ($row['excluded'] ?? false) === false));

        return ['candidates' => $candidates, 'scored' => $scored];
    }

    /**
     * Hiç kuryede kullanılabilir GPS yoksa veya tüm konumlar çok eskiyse: sadece aktif sipariş yüküne göre havuz.
     *
     * @return array{candidates: list<array<string, mixed>>, scored: list<array<string, mixed>>}
     */
    private function scoreCourierPoolLoadOnlyFallback(
        Order $locked,
        EloquentCollection $couriers,
        float $wActive,
    ): array {
        $courierIds = $couriers->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $activeCounts = $this->activeDeliveryCountsByCourier((int) $locked->firm_id, $courierIds);

        $candidates = [];
        $scored = [];
        foreach ($couriers as $c) {
            $activeCount = (int) ($activeCounts[(int) $c->id] ?? 0);
            $score = $wActive * $activeCount;
            $row = [
                'courier_id' => (int) $c->id,
                'excluded' => false,
                'reason' => 'fallback_load_only',
                'active_orders' => $activeCount,
                'score' => round($score, 6),
            ];
            $candidates[] = $row;
            $scored[] = $row;
        }

        usort($scored, function (array $a, array $b): int {
            $byScore = ($a['score'] <=> $b['score']);

            return $byScore !== 0 ? $byScore : (($a['courier_id'] ?? 0) <=> ($b['courier_id'] ?? 0));
        });

        return ['candidates' => $candidates, 'scored' => array_values($scored)];
    }

    /**
     * @param  list<int>  $courierIds
     * @return array<int, int> courier_id => aktif teslimat sipariş sayısı
     */
    private function activeDeliveryCountsByCourier(int $firmId, array $courierIds): array
    {
        if ($courierIds === []) {
            return [];
        }

        $rows = Order::query()
            ->where('firm_id', $firmId)
            ->whereIn('courier_id', $courierIds)
            ->whereIn('status', self::ACTIVE_DELIVERY_STATUSES)
            ->selectRaw('courier_id, count(*) as active_cnt')
            ->groupBy('courier_id')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $cid = (int) $row->courier_id;
            $out[$cid] = (int) $row->active_cnt;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $candidatesJson
     */
    private function logDecision(
        Order $order,
        string $trigger,
        ?int $createdByUserId,
        ?int $chosenCourierId,
        array $candidatesJson
    ): void {
        OrderDispatchDecision::query()->create([
            'order_id' => $order->id,
            'firm_id' => $order->firm_id,
            'chosen_courier_id' => $chosenCourierId,
            'candidates_json' => $candidatesJson,
            'trigger' => $trigger,
            'created_by_user_id' => $createdByUserId,
            'created_at' => now(),
        ]);
    }
}
