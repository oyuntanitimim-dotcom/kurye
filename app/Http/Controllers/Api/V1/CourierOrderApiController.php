<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderStateService;
use App\Modules\Couriers\Models\CourierLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourierOrderApiController extends Controller
{
    public function __construct(
        private readonly OrderStateService $orderStateService
    ) {}

    public function active(Request $request): JsonResponse
    {
        $c = $request->user()->courierProfile;
        if ($c === null) {
            abort(403);
        }

        $limit = (int) $request->integer('limit', 80);
        $limit = max(20, min(150, $limit));

        $loc = CourierLocation::query()->where('courier_id', $c->id)->latest('updated_at')->first();
        $locPayload = $loc === null ? null : [
            'latitude' => (float) $loc->latitude,
            'longitude' => (float) $loc->longitude,
            'updated_at' => $loc->updated_at?->toIso8601String(),
        ];

        $orders = Order::query()
            ->where('courier_id', $c->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->select([
                'id',
                'restaurant_id',
                'user_id',
                'delivery_address_id',
                'status',
                'customer_name',
                'customer_phone',
                'total_price',
                'delivery_fee',
                'notes',
                'created_at',
                'updated_at',
            ])
            ->with([
                'restaurant:id,name,latitude,longitude',
                'customer:id,name,phone',
                'deliveryAddress:id,address,latitude,longitude',
            ])
            ->latest()
            ->limit($limit)
            ->get();

        // Mobil istemci: haritada “kurye konumu” için son paylaşılan lokasyon.
        return response()->json($orders->map(function (Order $o) use ($locPayload) {
            $arr = $o->toArray();
            $arr['courier_location'] = $locPayload;
            return $arr;
        })->values());
    }

    /**
     * Firma panelinden atama sonrası: kurye görevi kabul eder → `courier_accepted`.
     */
    public function acceptAssignment(Request $request, Order $order): JsonResponse
    {
        $c = $request->user()->courierProfile;
        if ($c === null || (int) $order->courier_id !== (int) $c->id) {
            abort(403);
        }

        if ($order->status !== OrderStatus::CourierAssigned->value) {
            return response()->json(['message' => 'Bu sipariş atama onayı beklemiyor.'], 422);
        }

        $this->orderStateService->transition($order, OrderStatus::CourierAccepted, [
            'event' => 'courier_accepted_firm_assignment',
        ]);

        $fresh = $order->fresh(['restaurant', 'customer', 'deliveryAddress']);
        $loc = CourierLocation::query()->where('courier_id', $c->id)->first();
        $arr = $fresh->toArray();
        $arr['courier_location'] = $loc === null ? null : [
            'latitude' => (float) $loc->latitude,
            'longitude' => (float) $loc->longitude,
            'updated_at' => $loc->updated_at?->toIso8601String(),
        ];
        return response()->json($arr);
    }

    /**
     * Firma atamasını reddet / devir: sipariş tekrar «Hazır» havuzuna düşer, firmaya uyarı gider.
     */
    public function declineAssignment(Request $request, Order $order): JsonResponse
    {
        $c = $request->user()->courierProfile;
        if ($c === null || (int) $order->courier_id !== (int) $c->id) {
            abort(403);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'in:unavailable,vehicle_issue,transfer'],
        ]);

        if ($order->status !== OrderStatus::CourierAssigned->value) {
            return response()->json(['message' => 'Bu sipariş atama bekleyen durumda değil.'], 422);
        }

        DB::transaction(function () use ($order, $data): void {
            $order->courier_id = null;
            $order->save();
            $this->orderStateService->transition($order->fresh(), OrderStatus::Ready, [
                'event' => 'courier_declined_assignment',
                'reason' => $data['reason'],
            ]);
        });

        $fresh = $order->fresh();
        $reasonLabel = match ($data['reason']) {
            'unavailable' => 'Müsait değil',
            'vehicle_issue' => 'Araç arızası',
            'transfer' => 'Devir / başka kurye',
            default => $data['reason'],
        };

        $this->orderStateService->notifyFirmAdmins(
            $fresh,
            'Kurye atamayı reddetti',
            'Sipariş #'.$fresh->id.' — '.$reasonLabel.'. Yeni kurye ataması yapın.',
            ['event' => 'courier_declined_assignment', 'reason' => $data['reason']]
        );

        $fresh = $fresh->load(['restaurant', 'customer', 'deliveryAddress']);
        $loc = CourierLocation::query()->where('courier_id', $c->id)->first();
        $arr = $fresh->toArray();
        $arr['courier_location'] = $loc === null ? null : [
            'latitude' => (float) $loc->latitude,
            'longitude' => (float) $loc->longitude,
            'updated_at' => $loc->updated_at?->toIso8601String(),
        ];
        return response()->json($arr);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $c = $request->user()->courierProfile;
        if ($c === null || (int) $order->courier_id !== (int) $c->id) {
            abort(403);
        }

        $data = $request->validate([
            'status' => ['required', 'in:picked_up,on_the_way,delivered'],
        ]);

        $map = [
            'picked_up' => OrderStatus::PickedUp,
            'on_the_way' => OrderStatus::OnTheWay,
            'delivered' => OrderStatus::Delivered,
        ];

        $next = $map[$data['status']];
        $current = OrderStatus::tryFrom($order->status);

        if ($next === OrderStatus::PickedUp && $current !== OrderStatus::CourierAccepted) {
            return response()->json(['message' => 'Önce firmadan gelen atamayı kabul edin.'], 422);
        }

        if ($next === OrderStatus::OnTheWay && $current !== OrderStatus::PickedUp) {
            return response()->json(['message' => 'Önce siparişi aldığınızı işaretleyin.'], 422);
        }

        if ($next === OrderStatus::Delivered && ! in_array($current, [OrderStatus::PickedUp, OrderStatus::OnTheWay], true)) {
            return response()->json(['message' => 'Teslim için önce siparişi alıp yola çıkmalısınız.'], 422);
        }

        $this->orderStateService->transition($order, $next);

        $fresh = $order->fresh(['restaurant', 'customer', 'deliveryAddress']);
        $loc = CourierLocation::query()->where('courier_id', $c->id)->first();
        $arr = $fresh->toArray();
        $arr['courier_location'] = $loc === null ? null : [
            'latitude' => (float) $loc->latitude,
            'longitude' => (float) $loc->longitude,
            'updated_at' => $loc->updated_at?->toIso8601String(),
        ];
        return response()->json($arr);
    }
}
