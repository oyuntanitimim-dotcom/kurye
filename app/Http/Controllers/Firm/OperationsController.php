<?php

declare(strict_types=1);

namespace App\Http\Controllers\Firm;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Firms\Models\Firm;
use App\Infrastructure\Geo\Contracts\CourierGeoLocatorInterface;
use App\Modules\Couriers\Models\Courier;
use App\Support\FirmOperationsSnapshotOrderQuery;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class OperationsController extends Controller
{
    public function __construct(
        private readonly CourierGeoLocatorInterface $geoLocator
    ) {}

    public function index(): View
    {
        return view('firm.operations', [
            'title' => 'Operasyon',
            'geoRedisEnabled' => (bool) config('courier.geo_redis_enabled', false),
        ]);
    }

    public function snapshot(): JsonResponse
    {
        $firmId = (int) Auth::user()->firm_id;
        $firmModel = Firm::query()->find($firmId);
        $firmOp = $firmModel !== null ? $firmModel->mergedOperationSettings() : [];
        $maxAgeMinutes = max(1, (int) ($firmOp['location_max_age_minutes'] ?? config('courier.dispatch_location_max_age_minutes', 15)));
        $freshAfter = Carbon::now()->subMinutes($maxAgeMinutes);

        $couriers = Courier::query()
            ->where('firm_id', $firmId)
            ->where('status', 'active')
            ->with('location')
            ->orderBy('name')
            ->get();

        $activeCourierIds = $couriers->pluck('id')->all();
        $freshCourierIds = $couriers->filter(function (Courier $c) use ($freshAfter): bool {
            $loc = $c->location;
            if ($loc === null || $loc->updated_at === null) {
                return false;
            }

            return $loc->updated_at->greaterThanOrEqualTo($freshAfter);
        })->pluck('id')->all();

        $orders = FirmOperationsSnapshotOrderQuery::applyFiltrationForFirmSummary(
            Order::query()->where('firm_id', $firmId),
        )
            ->with(['restaurant', 'courier', 'deliveryAddress'])
            ->orderByDesc('id')
            ->limit((int) config('courier.operations_snapshot_order_limit', 80))
            ->get();

        $nearRadius = (float) config('courier.operations_near_radius_km', 5);
        $nearLimit = (int) config('courier.operations_near_limit', 8);

        $orderRows = $orders->map(function (Order $order) use ($firmId, $nearRadius, $nearLimit, $activeCourierIds, $freshCourierIds): array {
            $addr = $order->deliveryAddress;
            $lat = $addr !== null && $addr->latitude !== null ? (float) $addr->latitude : null;
            $lng = $addr !== null && $addr->longitude !== null ? (float) $addr->longitude : null;
            $hasCoords = $lat !== null && $lng !== null;

            $suggestedNearbyCourierIds = [];
            if (
                config('courier.geo_redis_enabled')
                && $hasCoords
                && $order->status === OrderStatus::Ready->value
                && $order->courier_id === null
                && $order->restaurant_courier_requested_at !== null
            ) {
                $ids = $this->geoLocator->courierIdsNear($firmId, $lat, $lng, $nearRadius, $nearLimit);
                $suggestedNearbyCourierIds = array_values(array_intersect($ids, $activeCourierIds, $freshCourierIds));
            }

            return [
                'id' => $order->id,
                'source' => $order->source,
                'source_label' => OrderSource::tryFrom((string) $order->source)?->label() ?? $order->source,
                'status' => $order->status,
                'status_label' => OrderStatus::tryFrom($order->status)?->label() ?? $order->status,
                'restaurant' => $order->restaurant === null ? null : [
                    'id' => $order->restaurant->id,
                    'name' => $order->restaurant->name,
                    'lat' => $order->restaurant->latitude !== null ? (float) $order->restaurant->latitude : null,
                    'lng' => $order->restaurant->longitude !== null ? (float) $order->restaurant->longitude : null,
                ],
                'courier_id' => $order->courier_id,
                'restaurant_courier_requested' => $order->restaurant_courier_requested_at !== null,
                'delivery' => [
                    'lat' => $lat,
                    'lng' => $lng,
                    'has_coordinates' => $hasCoords,
                    'address' => ($addr !== null && $addr->address !== null && trim((string) $addr->address) !== '')
                        ? trim((string) $addr->address)
                        : null,
                    'show_on_map' => FirmOperationsSnapshotOrderQuery::showsDeliveryDestinationOnOperationsMap($order, $hasCoords),
                ],
                'suggested_nearby_courier_ids' => $suggestedNearbyCourierIds,
            ];
        });

        $courierRows = $couriers->map(function (Courier $c) use ($freshAfter): array {
            $loc = $c->location;
            $isStale = true;
            if ($loc !== null && $loc->updated_at !== null) {
                $isStale = $loc->updated_at->lessThan($freshAfter);
            }

            return [
                'id' => $c->id,
                'name' => $c->name,
                'status' => $c->status,
                'lat' => $loc !== null ? (float) $loc->latitude : null,
                'lng' => $loc !== null ? (float) $loc->longitude : null,
                'location_updated_at' => $loc?->updated_at?->toIso8601String(),
                'is_stale' => $isStale,
            ];
        });

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'geo_redis_enabled' => (bool) config('courier.geo_redis_enabled'),
            'firm' => [
                'auto_dispatch_enabled' => (bool) ($firmOp['auto_dispatch_enabled'] ?? false),
            ],
            'settings' => [
                'location_max_age_minutes' => $maxAgeMinutes,
            ],
            'orders' => $orderRows,
            'couriers' => $courierRows,
        ]);
    }

    public function nearbyCouriers(Courier $courier): JsonResponse
    {
        $firmId = (int) Auth::user()->firm_id;
        if ((int) $courier->firm_id !== $firmId) {
            abort(404);
        }

        if (! (bool) config('courier.geo_redis_enabled', false)) {
            return response()->json(['ok' => false, 'reason' => 'geo_disabled', 'couriers' => []]);
        }

        $courier->loadMissing('location');
        $loc = $courier->location;
        if ($loc === null || $loc->latitude === null || $loc->longitude === null) {
            return response()->json(['ok' => false, 'reason' => 'no_location', 'couriers' => []]);
        }

        $firmModel = Firm::query()->find($firmId);
        $firmOp = $firmModel !== null ? $firmModel->mergedOperationSettings() : [];
        $maxAgeMinutes = max(1, (int) ($firmOp['location_max_age_minutes'] ?? config('courier.dispatch_location_max_age_minutes', 15)));
        $freshAfter = Carbon::now()->subMinutes($maxAgeMinutes);

        $radiusKm = (float) config('courier.operations_near_radius_km', 5);
        $limit = max(3, (int) config('courier.operations_near_limit', 8));

        // Fetch extra to account for filtering (self, stale, inactive)
        $ids = $this->geoLocator->courierIdsNear(
            $firmId,
            (float) $loc->latitude,
            (float) $loc->longitude,
            $radiusKm,
            $limit + 8
        );

        $ids = array_values(array_filter($ids, fn (int $id) => $id !== (int) $courier->id));
        if ($ids === []) {
            return response()->json(['ok' => true, 'couriers' => []]);
        }

        $near = Courier::query()
            ->where('firm_id', $firmId)
            ->where('status', 'active')
            ->whereIn('id', $ids)
            ->with('location')
            ->get()
            ->filter(function (Courier $c) use ($freshAfter): bool {
                $l = $c->location;
                return $l !== null && $l->updated_at !== null && $l->updated_at->greaterThanOrEqualTo($freshAfter);
            })
            ->values()
            ->take($limit);

        $rows = $near->map(function (Courier $c): array {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'status' => $c->status,
            ];
        })->all();

        return response()->json(['ok' => true, 'couriers' => $rows]);
    }
}
