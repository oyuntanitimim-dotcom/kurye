<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Firma raporları: web (/firma/raporlar) ve mobil API için aynı sorgular.
 */
final class FirmReportInsights
{
    /**
     * @return array{
     *   byStatus: Collection<int|string, int>,
     *   orderTotal: int,
     *   courierDeliveredByPayment: object|null,
     *   revenueDelivered: float,
     *   deliveryFeesDelivered: float,
     *   discountsDelivered: float,
     *   platformFeesDelivered: float,
     *   restaurantCommissionDelivered: float,
     *   courierPayoutsDelivered: float,
     *   deliveredShopCount: int,
     *   deliveredShopRevenue: float,
     *   courierBreakdown: \Illuminate\Support\Collection<int, \App\Modules\Orders\Models\Order>,
     *   topRestaurantsByRevenue: \Illuminate\Support\Collection,
     *   topCategoriesByRevenue: \Illuminate\Support\Collection,
     *   topProductsByRevenue: \Illuminate\Support\Collection
     * }
     */
    public static function forFirmScope(int $firmId, ?int $restaurantId, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $ordersScope = Order::query()->where('firm_id', $firmId);
        if ($restaurantId !== null && $restaurantId > 0) {
            $ordersScope->where('restaurant_id', $restaurantId);
        }

        if ($dateFrom !== null) {
            $ordersScope->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $ordersScope->whereDate('created_at', '<=', $dateTo);
        }

        $byStatus = (clone $ordersScope)
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status');

        $delivered = OrderStatus::Delivered->value;

        $deliveredScope = Order::query()
            ->where('firm_id', $firmId)
            ->where('status', $delivered);
        if ($restaurantId !== null && $restaurantId > 0) {
            $deliveredScope->where('restaurant_id', $restaurantId);
        }
        if ($dateFrom !== null) {
            $deliveredScope->whereDate('updated_at', '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $deliveredScope->whereDate('updated_at', '<=', $dateTo);
        }

        $courierDeliveredByPaymentBase = Order::query()
            ->where('firm_id', $firmId)
            ->where('status', $delivered)
            ->whereNotNull('courier_id');
        if ($restaurantId !== null && $restaurantId > 0) {
            $courierDeliveredByPaymentBase->where('restaurant_id', $restaurantId);
        }
        if ($dateFrom !== null) {
            $courierDeliveredByPaymentBase->whereDate('updated_at', '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $courierDeliveredByPaymentBase->whereDate('updated_at', '<=', $dateTo);
        }

        $courierDeliveredByPayment = (clone $courierDeliveredByPaymentBase)->selectRaw("
            coalesce(sum(case when payment_method = 'online' then 1 else 0 end), 0) as cnt_online,
            coalesce(sum(case when payment_method = 'online' then total_price else 0 end), 0) as rev_online,
            coalesce(sum(case when payment_method in ('card_on_delivery','card') then 1 else 0 end), 0) as cnt_card,
            coalesce(sum(case when payment_method in ('card_on_delivery','card') then total_price else 0 end), 0) as rev_card,
            coalesce(sum(case when payment_method = 'cash_on_delivery' then 1 else 0 end), 0) as cnt_cash,
            coalesce(sum(case when payment_method = 'cash_on_delivery' then total_price else 0 end), 0) as rev_cash,
            coalesce(sum(case when coalesce(payment_method, '') not in ('online','card_on_delivery','card','cash_on_delivery') then 1 else 0 end), 0) as cnt_other,
            coalesce(sum(case when coalesce(payment_method, '') not in ('online','card_on_delivery','card','cash_on_delivery') then total_price else 0 end), 0) as rev_other
        ")->first();

        $deliveredSums = (clone $deliveredScope)->selectRaw('
            coalesce(sum(total_price), 0) as revenue,
            coalesce(sum(delivery_fee), 0) as delivery_fees,
            coalesce(sum(discount_amount), 0) as discounts,
            coalesce(sum(platform_fee_amount), 0) as platform_fees,
            coalesce(sum(restaurant_commission_amount), 0) as restaurant_commission,
            coalesce(sum(courier_payout_amount), 0) as courier_payouts,
            sum(case when courier_id is null then 1 else 0 end) as delivered_shop_count,
            coalesce(sum(case when courier_id is null then total_price else 0 end), 0) as delivered_shop_revenue
        ')->first();

        $revenueDelivered = (float) ($deliveredSums?->revenue ?? 0);
        $deliveryFeesDelivered = (float) ($deliveredSums?->delivery_fees ?? 0);
        $discountsDelivered = (float) ($deliveredSums?->discounts ?? 0);
        $platformFeesDelivered = (float) ($deliveredSums?->platform_fees ?? 0);
        $restaurantCommissionDelivered = (float) ($deliveredSums?->restaurant_commission ?? 0);
        $courierPayoutsDelivered = (float) ($deliveredSums?->courier_payouts ?? 0);
        $deliveredShopCount = (int) ($deliveredSums?->delivered_shop_count ?? 0);
        $deliveredShopRevenue = (float) ($deliveredSums?->delivered_shop_revenue ?? 0);

        $deliveredOrderCount = (int) (clone $deliveredScope)->count();

        $courierBreakdown = (clone $deliveredScope)
            ->whereNotNull('courier_id')
            ->select(
                'courier_id',
                DB::raw('count(*) as delivered_count'),
                DB::raw('coalesce(sum(total_price), 0) as revenue'),
                DB::raw('coalesce(sum(delivery_fee), 0) as delivery_fees'),
                DB::raw('coalesce(sum(discount_amount), 0) as discounts'),
                DB::raw('coalesce(sum(platform_fee_amount), 0) as platform_fees'),
                DB::raw('coalesce(sum(restaurant_commission_amount), 0) as restaurant_commission'),
                DB::raw('coalesce(sum(courier_payout_amount), 0) as courier_payout')
            )
            ->groupBy('courier_id')
            ->with('courier:id,name')
            ->orderByDesc('courier_payout')
            ->get();

        $itemScope = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.firm_id', $firmId)
            ->where('o.status', $delivered);
        if ($restaurantId !== null && $restaurantId > 0) {
            $itemScope->where('o.restaurant_id', $restaurantId);
        }
        if ($dateFrom !== null) {
            $itemScope->whereDate('o.updated_at', '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $itemScope->whereDate('o.updated_at', '<=', $dateTo);
        }

        $topRestaurantsByRevenue = (clone $itemScope)
            ->leftJoin('restaurants as r', 'r.id', '=', 'o.restaurant_id')
            ->selectRaw('o.restaurant_id, coalesce(r.name, "—") as restaurant_name, coalesce(sum(oi.price * oi.quantity), 0) as revenue, coalesce(sum(oi.quantity), 0) as qty')
            ->groupBy('o.restaurant_id', 'r.name')
            ->orderByDesc('revenue')
            ->limit(12)
            ->get();

        $topCategoriesByRevenue = (clone $itemScope)
            ->leftJoin('products as p', 'p.id', '=', 'oi.product_id')
            ->leftJoin('restaurant_categories as c', 'c.id', '=', 'p.category_id')
            ->selectRaw('c.id as category_id, coalesce(c.name, "Kategorisiz") as category_name, coalesce(sum(oi.price * oi.quantity), 0) as revenue, coalesce(sum(oi.quantity), 0) as qty')
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('revenue')
            ->limit(12)
            ->get();

        $topProductsByRevenue = (clone $itemScope)
            ->leftJoin('products as p', 'p.id', '=', 'oi.product_id')
            ->selectRaw('oi.product_id, coalesce(p.name, oi.product_name, "—") as product_name, coalesce(sum(oi.price * oi.quantity), 0) as revenue, coalesce(sum(oi.quantity), 0) as qty')
            ->groupBy('oi.product_id', 'p.name', 'oi.product_name')
            ->orderByDesc('revenue')
            ->limit(15)
            ->get();

        $orderTotal = (clone $ordersScope)->count();

        return [
            'byStatus' => $byStatus,
            'deliveredOrderCount' => $deliveredOrderCount,
            'orderTotal' => $orderTotal,
            'courierDeliveredByPayment' => $courierDeliveredByPayment,
            'revenueDelivered' => $revenueDelivered,
            'deliveryFeesDelivered' => $deliveryFeesDelivered,
            'discountsDelivered' => $discountsDelivered,
            'platformFeesDelivered' => $platformFeesDelivered,
            'restaurantCommissionDelivered' => $restaurantCommissionDelivered,
            'courierPayoutsDelivered' => $courierPayoutsDelivered,
            'deliveredShopCount' => $deliveredShopCount,
            'deliveredShopRevenue' => $deliveredShopRevenue,
            'courierBreakdown' => $courierBreakdown,
            'topRestaurantsByRevenue' => $topRestaurantsByRevenue,
            'topCategoriesByRevenue' => $topCategoriesByRevenue,
            'topProductsByRevenue' => $topProductsByRevenue,
        ];
    }
}
