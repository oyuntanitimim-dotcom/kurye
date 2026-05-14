<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Restaurant;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function __construct(
        private readonly OrderStateService $orderStateService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || $u->restaurant_id === null) {
            abort(403);
        }

        $limit = (int) $request->integer('limit', 100);
        $limit = max(20, min(200, $limit));

        $orders = Order::query()
            ->where('restaurant_id', (int) $u->restaurant_id)
            ->select([
                'id',
                'restaurant_id',
                'courier_id',
                'user_id',
                'delivery_address_id',
                'status',
                'customer_name',
                'customer_phone',
                'total_price',
                'delivery_fee',
                'notes',
                'restaurant_courier_requested_at',
                'created_at',
                'updated_at',
            ])
            ->with([
                'customer:id,name,phone',
                'deliveryAddress:id,address,latitude,longitude',
                'courier:id,name,phone',
            ])
            ->latest()
            ->limit($limit)
            ->get();

        return response()->json($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $u = $request->user();
        if ($u === null || (int) $order->restaurant_id !== (int) $u->restaurant_id) {
            abort(403);
        }

        $order->load([
            'items',
            'customer',
            'deliveryAddress',
            'courier',
            'restaurant',
            'statusHistories' => fn ($q) => $q->orderBy('created_at'),
        ]);

        return response()->json($order);
    }

    public function requestCourier(Request $request, Order $order): JsonResponse
    {
        $u = $request->user();
        if ($u === null || (int) $order->restaurant_id !== (int) $u->restaurant_id) {
            abort(403);
        }

        if ($order->status !== OrderStatus::Ready->value) {
            return response()->json(['ok' => false, 'message' => 'Sadece hazır siparişlerde kurye çağrılabilir.'], 422);
        }

        if ($order->courier_id !== null) {
            return response()->json(['ok' => false, 'message' => 'Bu siparişe zaten kurye atanmış.'], 422);
        }

        if ($order->restaurant_courier_requested_at !== null) {
            return response()->json(['ok' => true, 'message' => 'Kurye şirketine zaten haber verildi.']);
        }

        $order->update(['restaurant_courier_requested_at' => now()]);

        return response()->json(['ok' => true, 'message' => 'Kurye şirketi bilgilendirildi.']);
    }

    public function accept(Request $request, Order $order): JsonResponse
    {
        $u = $request->user();
        if ($u === null || (int) $order->restaurant_id !== (int) $u->restaurant_id) {
            abort(403);
        }
        if ($order->status !== OrderStatus::Pending->value) {
            return response()->json(['ok' => false, 'message' => 'Sipariş bu durumda onaylanamaz.'], 422);
        }
        $this->orderStateService->transition($order, OrderStatus::Accepted);

        return response()->json(['ok' => true]);
    }

    public function preparing(Request $request, Order $order): JsonResponse
    {
        $u = $request->user();
        if ($u === null || (int) $order->restaurant_id !== (int) $u->restaurant_id) {
            abort(403);
        }
        $this->orderStateService->transition($order, OrderStatus::Preparing);

        return response()->json(['ok' => true]);
    }

    public function ready(Request $request, Order $order): JsonResponse
    {
        $u = $request->user();
        if ($u === null || (int) $order->restaurant_id !== (int) $u->restaurant_id) {
            abort(403);
        }
        $this->orderStateService->transition($order, OrderStatus::Ready);

        return response()->json(['ok' => true]);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        $u = $request->user();
        if ($u === null || (int) $order->restaurant_id !== (int) $u->restaurant_id) {
            abort(403);
        }
        if (! in_array($order->status, [
            OrderStatus::Pending->value,
            OrderStatus::Accepted->value,
            OrderStatus::Preparing->value,
        ], true)) {
            return response()->json(['ok' => false, 'message' => 'Bu durumda sipariş iptal edilemez.'], 422);
        }
        $this->orderStateService->transition($order, OrderStatus::Cancelled);

        return response()->json(['ok' => true]);
    }
}

