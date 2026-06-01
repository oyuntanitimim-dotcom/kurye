<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Firm;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Services\FirmCreditService;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignCourierController extends Controller
{
    public function __construct(
        private readonly OrderStateService $orderStateService,
        private readonly FirmCreditService $firmCreditService
    ) {}

    public function __invoke(Request $request, Order $order): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }

        $firmId = (int) $u->firm_id;
        if ((int) $order->firm_id !== $firmId) {
            abort(403);
        }

        $data = $request->validate([
            'courier_id' => ['required', 'integer', 'exists:couriers,id'],
        ]);

        $courier = Courier::query()
            ->where('firm_id', $firmId)
            ->whereKey((int) $data['courier_id'])
            ->firstOrFail();

        $previousCourierId = $order->courier_id !== null ? (int) $order->courier_id : null;
        if ($previousCourierId !== null && $previousCourierId === (int) $courier->id) {
            return response()->json(['ok' => true, 'message' => 'Bu kurye zaten atanmış.']);
        }

        if (
            $order->status === OrderStatus::Ready->value
            && $previousCourierId === null
            && $order->restaurant_courier_requested_at === null
        ) {
            return response()->json(['ok' => false, 'message' => 'Restoran henüz kurye çağırmadı.'], 422);
        }

        if (! $this->firmCreditService->canAssign($order)) {
            return response()->json([
                'ok' => false,
                'message' => 'Kontör yetersiz. Lütfen kontör yükleyin.',
                'reason' => 'insufficient_credit',
            ], 402);
        }

        $reassignStatuses = [
            OrderStatus::CourierAssigned->value,
            OrderStatus::CourierAccepted->value,
            OrderStatus::PickedUp->value,
            OrderStatus::OnTheWay->value,
        ];

        $order->update(['courier_id' => $courier->id]);
        $fresh = $order->fresh();

        if (
            $fresh !== null
            && $previousCourierId !== null
            && (int) $courier->id !== $previousCourierId
            && $fresh->status === OrderStatus::CourierAccepted->value
        ) {
            $this->orderStateService->transition($fresh, OrderStatus::CourierAssigned, [
                'event' => 'firm_reassigned_after_courier_accepted',
            ]);
            $fresh = $order->fresh();
            if ($fresh !== null) {
                $this->orderStateService->recordCourierReassignment($fresh, $previousCourierId, (int) $courier->id);
            }

            return response()->json(['ok' => true, 'message' => 'Kurye değiştirildi; yeni kurye onay bekliyor.', 'order' => $fresh]);
        }

        if ($fresh !== null && in_array((string) $fresh->status, $reassignStatuses, true)) {
            $this->orderStateService->recordCourierReassignment($fresh, $previousCourierId, (int) $courier->id);
            return response()->json(['ok' => true, 'message' => 'Kurye güncellendi.', 'order' => $fresh]);
        }

        if ($fresh !== null) {
            $this->orderStateService->transition($fresh, OrderStatus::CourierAssigned);
        }

        return response()->json(['ok' => true, 'message' => 'Kurye atandı.', 'order' => $fresh]);
    }
}

