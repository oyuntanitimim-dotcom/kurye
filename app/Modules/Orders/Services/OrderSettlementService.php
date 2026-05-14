<?php

declare(strict_types=1);

namespace App\Modules\Orders\Services;

use App\Enums\CourierCompensationType;
use App\Modules\Orders\Models\Order;

class OrderSettlementService
{
    /**
     * İşletmeden teslim başı sabit ücret (₺); sipariş matrahı kullanılmaz.
     *
     * @return array{platform_fee_amount: string, restaurant_commission_amount: string, courier_payout_amount: ?string}
     */
    public function snapshotForDelivery(Order $order): array
    {
        $order->loadMissing('firm', 'restaurant', 'courier');

        $platform = (float) ($order->firm?->platform_fee_per_order ?? 0);
        $platform = max(0, round($platform, 2));

        $restaurantFee = $order->restaurant?->fee_per_delivery;
        if ($restaurantFee === null) {
            $restaurantFee = $order->firm?->default_restaurant_fee_per_delivery ?? 0;
        }
        $restaurantFee = max(0, round((float) $restaurantFee, 2));

        return [
            'platform_fee_amount' => number_format($platform, 2, '.', ''),
            'restaurant_commission_amount' => number_format($restaurantFee, 2, '.', ''),
            'courier_payout_amount' => $this->courierPayoutSnapshot($order),
        ];
    }

    /**
     * Yalnızca "teslim başı sabit ücret" modelinde otomatik snapshot; maaş/km için mesafe ve bordro dışarıda.
     */
    private function courierPayoutSnapshot(Order $order): ?string
    {
        if (! $order->courier_id || $order->courier === null) {
            return null;
        }

        $courier = $order->courier;
        $type = CourierCompensationType::tryFrom((string) $courier->compensation_type)
            ?? CourierCompensationType::None;

        if ($type !== CourierCompensationType::PerDelivery) {
            return null;
        }

        if ($courier->compensation_per_delivery === null) {
            return null;
        }

        $v = max(0, round((float) $courier->compensation_per_delivery, 2));

        return number_format($v, 2, '.', '');
    }
}
