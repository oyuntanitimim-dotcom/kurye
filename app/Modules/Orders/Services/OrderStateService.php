<?php

namespace App\Modules\Orders\Services;

use App\Enums\OrderStatus;
use App\Jobs\CreateInAppNotificationJob;
use App\Jobs\PushMarketplaceOrderStatusJob;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Services\FirmCreditService;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderStatusHistory;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\DB;

class OrderStateService
{
    public function __construct(
        private readonly OrderSettlementService $orderSettlementService,
        private readonly FirmCreditService $firmCreditService
    ) {}

    /**
     * Sipariş durumunu değiştirmeden kurye değiştirir; geçmişe meta kaydı ve bildirimler.
     *
     * @param  int|null  $previousCourierId  Güncelleme öncesi orders.courier_id
     */
    public function recordCourierReassignment(Order $order, ?int $previousCourierId, int $newCourierId): void
    {
        DB::transaction(function () use ($order, $previousCourierId, $newCourierId): void {
            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => $order->status,
                'meta' => [
                    'event' => 'courier_reassigned',
                    'from_courier_id' => $previousCourierId,
                    'to_courier_id' => $newCourierId,
                ],
                'created_at' => now(),
            ]);
        });

        $order->load('courier');
        $this->notifyCourierReassignment($order, $previousCourierId);
    }

    public function transition(Order $order, OrderStatus $next, array $meta = []): void
    {
        DB::transaction(function () use ($order, $next, $meta): void {
            if ($next === OrderStatus::CourierAssigned) {
                // Kurye ataması anında sipariş başına kontör düşülür (idempotent).
                $this->firmCreditService->chargeForAssignment($order);
            }

            if ($next === OrderStatus::Delivered) {
                $order->loadMissing('firm', 'restaurant');
                $snap = $this->orderSettlementService->snapshotForDelivery($order);
                $order->platform_fee_amount = $snap['platform_fee_amount'];
                $order->restaurant_commission_amount = $snap['restaurant_commission_amount'];
                $order->courier_payout_amount = $snap['courier_payout_amount'];
            }

            $order->status = $next->value;
            if (in_array($next, [OrderStatus::Delivered, OrderStatus::Cancelled], true)) {
                $order->tracking_revoked_at = now();
            }
            $order->save();

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => $next->value,
                'meta' => $meta,
                'created_at' => now(),
            ]);

            $this->notifyParticipants($order, $next);
        });

        $this->maybePushMarketplaceStatus($order->fresh(), $next);
    }

    /** Firma yöneticilerine (aynı firmadaki firm_admin) uygulama içi bildirim. */
    public function notifyFirmAdmins(Order $order, string $title, string $message, array $data = []): void
    {
        User::query()
            ->where('firm_id', $order->firm_id)
            ->whereHas('role', fn ($q) => $q->where('name', Role::FIRM_ADMIN))
            ->each(function (User $u) use ($title, $message, $order, $data): void {
                CreateInAppNotificationJob::dispatch(
                    $u->id,
                    $title,
                    $message,
                    array_merge(['order_id' => $order->id], $data)
                )->afterCommit();
            });
    }

    private function notifyCourierReassignment(Order $order, ?int $previousCourierId): void
    {
        $customer = $order->customer;
        $title = 'Kurye güncellendi';
        $message = 'Sipariş #'.$order->id.' — yeni kurye atandı.';

        if ($customer !== null) {
            CreateInAppNotificationJob::dispatch(
                $customer->id,
                $title,
                $message,
                ['order_id' => $order->id, 'event' => 'courier_reassigned']
            )->afterCommit();
        }

        User::query()
            ->where('restaurant_id', $order->restaurant_id)
            ->whereHas('role', fn ($q) => $q->where('name', Role::RESTAURANT))
            ->each(function (User $u) use ($title, $message, $order): void {
                CreateInAppNotificationJob::dispatch(
                    $u->id,
                    $title,
                    $message,
                    ['order_id' => $order->id]
                )->afterCommit();
            });

        if ($previousCourierId !== null) {
            $prev = Courier::query()->find($previousCourierId);
            if ($prev?->user_id !== null) {
                CreateInAppNotificationJob::dispatch(
                    $prev->user_id,
                    'Atama kaldırıldı',
                    'Sipariş #'.$order->id.' artık size atanmadı.',
                    ['order_id' => $order->id]
                )->afterCommit();
            }
        }

        if ($order->courier_id) {
            $order->load('courier');
            if ($order->courier?->user_id) {
                CreateInAppNotificationJob::dispatch(
                    $order->courier->user_id,
                    $title,
                    $message,
                    ['order_id' => $order->id]
                )->afterCommit();
            }
        }
    }

    private function notifyParticipants(Order $order, OrderStatus $status): void
    {
        $customer = $order->customer;
        $title = 'Sipariş güncellendi';
        $message = 'Sipariş #'.$order->id.' — '.$status->label();

        if ($customer !== null) {
            CreateInAppNotificationJob::dispatch(
                $customer->id,
                $title,
                $message,
                ['order_id' => $order->id, 'status' => $status->value]
            )->afterCommit();
        }

        User::query()
            ->where('restaurant_id', $order->restaurant_id)
            ->whereHas('role', fn ($q) => $q->where('name', Role::RESTAURANT))
            ->each(function (User $u) use ($title, $message, $order): void {
                CreateInAppNotificationJob::dispatch(
                    $u->id,
                    $title,
                    $message,
                    ['order_id' => $order->id]
                )->afterCommit();
            });

        if ($order->courier_id) {
            $order->load('courier');
            if ($order->courier?->user_id) {
                CreateInAppNotificationJob::dispatch(
                    $order->courier->user_id,
                    $title,
                    $message,
                    ['order_id' => $order->id]
                )->afterCommit();
            }
        }
    }

    private function maybePushMarketplaceStatus(Order $order, OrderStatus $status): void
    {
        if (! in_array($status, [OrderStatus::Ready, OrderStatus::Cancelled], true)) {
            return;
        }

        $order->load('integrationExternalOrder');
        if ($order->integrationExternalOrder === null) {
            return;
        }

        PushMarketplaceOrderStatusJob::dispatch($order->id, $status->value)->afterCommit();
    }
}
