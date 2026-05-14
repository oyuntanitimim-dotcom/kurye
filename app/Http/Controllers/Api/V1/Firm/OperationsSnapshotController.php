<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Firm;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Infrastructure\Geo\Contracts\CourierGeoLocatorInterface;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Models\Firm;
use App\Support\FirmOperationsSnapshotOrderQuery;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OperationsSnapshotController extends Controller
{
    public function __construct(private readonly CourierGeoLocatorInterface $geoLocator) {}

    public function __invoke(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }

        $firmId = (int) $u->firm_id;
        $firmModel = Firm::query()->find($firmId);
        $firmOp = $firmModel !== null ? $firmModel->mergedOperationSettings() : [];
        $maxAgeMinutes = max(1, (int) ($firmOp['location_max_age_minutes'] ?? config('courier.dispatch_location_max_age_minutes', 15)));
        $freshAfter = Carbon::now()->subMinutes($maxAgeMinutes);
        $includeAddress = $request->boolean('include_address', true);
        $orderLimit = (int) $request->integer('order_limit', (int) config('courier.operations_snapshot_order_limit', 80));
        $orderLimit = max(20, min(200, $orderLimit));

        $couriers = Courier::query()
            ->select(['id', 'firm_id', 'name', 'status'])
            ->where('firm_id', $firmId)
            ->where('status', 'active')
            ->with(['location:courier_id,latitude,longitude,updated_at'])
            ->orderBy('name')
            ->get();

        $activeCourierIds = $couriers->pluck('id')->all();
        $freshCourierIds = $couriers->filter(function (Courier $c) use ($freshAfter): bool {
            $loc = $c->location;
            return $loc !== null && $loc->updated_at !== null && $loc->updated_at->greaterThanOrEqualTo($freshAfter);
        })->pluck('id')->all();

        $orders = FirmOperationsSnapshotOrderQuery::applyFiltrationForFirmSummary(
            Order::query()->where('firm_id', $firmId),
        )
            ->select([
                'id',
                'firm_id',
                'restaurant_id',
                'courier_id',
                'delivery_address_id',
                'source',
                'status',
                'restaurant_courier_requested_at',
            ])
            ->with([
                'restaurant:id,name,latitude,longitude',
                'deliveryAddress:id,address,latitude,longitude',
            ])
            ->orderByDesc('id')
            ->limit($orderLimit)
            ->get();

        $nearRadius = (float) config('courier.operations_near_radius_km', 5);
        $nearLimit = (int) config('courier.operations_near_limit', 8);

        $orderRows = $orders->map(function (Order $order) use ($firmId, $nearRadius, $nearLimit, $activeCourierIds, $freshCourierIds, $includeAddress): array {
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
                    'address' => $includeAddress && ($addr !== null && $addr->address !== null && trim((string) $addr->address) !== '')
                        ? trim((string) $addr->address)
                        : null,
                    /** Haritada turuncu varış göster (kurye atanmış teslim koordinatları) — web ile aynı kural */
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
                'auto_assign_best_after_eta' => (bool) ($firmOp['auto_assign_best_after_eta'] ?? false),
            ],
            'settings' => [
                'location_max_age_minutes' => $maxAgeMinutes,
            ],
            'orders' => $orderRows,
            'couriers' => $courierRows,
        ]);
    }
}

