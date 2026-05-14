<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class FinanceReporting
{
    public static function onlineOrdersOnlyEnabled(): bool
    {
        return (bool) config('kurye.finance_online_orders_only', false);
    }

    /**
     * @param  EloquentBuilder<\App\Modules\Orders\Models\Order>|QueryBuilder  $query
     */
    public static function restrictToOnlinePayment(EloquentBuilder|QueryBuilder $query, string $paymentColumn = 'payment_method'): void
    {
        if (! self::onlineOrdersOnlyEnabled()) {
            return;
        }

        $query->where($paymentColumn, 'online');
    }
}
