<?php

declare(strict_types=1);

namespace App\Support;

use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Builder;

/**
 * Firma Web (`/firma/operasyon/ozet`) ile Mobil API snapshot aynı sipariş kümesini döndürmelidir — tek BUILDER kaynağı.
 */
final class FirmOperationsSnapshotOrderQuery
{
    /**
     * @var list<string>
     */
    public const ACTIVE_STATUSES = [
        'pending',
        'accepted',
        'preparing',
        'ready',
        'courier_assigned',
        'courier_accepted',
        'picked_up',
        'on_the_way',
    ];

    /**
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public static function applyFiltrationForFirmSummary(Builder $query): Builder
    {
        return $query
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where(function ($q): void {
                $q->whereNotNull('restaurant_courier_requested_at')
                    ->orWhereNotNull('courier_id');
            });
    }

    /** Teslimat haritasında turuncu pin: adres varken kuryeye atanmış olmalı. */
    public static function showsDeliveryDestinationOnOperationsMap(Order $order, bool $hasCoords): bool
    {
        return $hasCoords
            && $order->courier_id !== null
            && $order->courier_id >= 1;
    }
}
