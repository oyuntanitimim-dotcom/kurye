<?php

namespace App\Http\Controllers\Restaurant;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $restaurantId = Auth::user()->restaurant_id;

        $delivered = OrderStatus::Delivered->value;
        $cancelled = OrderStatus::Cancelled->value;

        $preset = $request->filled('preset') ? $request->string('preset')->toString() : null;
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, $preset);

        $ordersScope = Order::query()->where('restaurant_id', $restaurantId);
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

        $deliveredScope = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', $delivered);
        if ($dateFrom !== null) {
            $deliveredScope->whereDate('updated_at', '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $deliveredScope->whereDate('updated_at', '<=', $dateTo);
        }

        $courierDeliveredByPaymentBase = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', $delivered)
            ->whereNotNull('courier_id');
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

        $sums = (clone $deliveredScope)->selectRaw('
            coalesce(sum(total_price), 0) as revenue,
            coalesce(sum(delivery_fee), 0) as delivery_fees,
            coalesce(sum(discount_amount), 0) as discounts,
            coalesce(sum(case when courier_id is not null then restaurant_commission_amount else 0 end), 0) as package_fees,
            sum(case when courier_id is null then 1 else 0 end) as delivered_shop_count,
            coalesce(sum(case when courier_id is null then total_price else 0 end), 0) as delivered_shop_revenue,
            sum(case when courier_id is not null then 1 else 0 end) as delivered_courier_count,
            coalesce(sum(case when courier_id is not null then total_price else 0 end), 0) as delivered_courier_revenue
        ')->first();

        $revenueDelivered = (float) ($sums?->revenue ?? 0);
        $deliveryFeesDelivered = (float) ($sums?->delivery_fees ?? 0);
        $discountsDelivered = (float) ($sums?->discounts ?? 0);
        $packageFeesDelivered = (float) ($sums?->package_fees ?? 0);
        $deliveredShopCount = (int) ($sums?->delivered_shop_count ?? 0);
        $deliveredShopRevenue = (float) ($sums?->delivered_shop_revenue ?? 0);
        $deliveredCourierCount = (int) ($sums?->delivered_courier_count ?? 0);

        $deliveredCourierCountAllPayments = (int) (($courierDeliveredByPayment->cnt_online ?? 0)
            + ($courierDeliveredByPayment->cnt_card ?? 0)
            + ($courierDeliveredByPayment->cnt_cash ?? 0)
            + ($courierDeliveredByPayment->cnt_other ?? 0));

        $deliveredCount = (clone $deliveredScope)->count();

        $cancelledScope = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', $cancelled);
        if ($dateFrom !== null) {
            $cancelledScope->whereDate('updated_at', '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $cancelledScope->whereDate('updated_at', '<=', $dateTo);
        }
        $cancelledCount = (clone $cancelledScope)->count();

        $courierDelivered = (clone $deliveredScope)
            ->whereNotNull('courier_id')
            ->select(
                'courier_id',
                DB::raw('count(*) as delivered_count'),
                DB::raw('coalesce(sum(total_price), 0) as revenue'),
                DB::raw('coalesce(sum(delivery_fee), 0) as delivery_fees'),
                DB::raw('coalesce(sum(discount_amount), 0) as discounts')
            )
            ->groupBy('courier_id')
            ->with('courier:id,name')
            ->orderByDesc('delivered_count')
            ->get();

        $courierCancelled = (clone $cancelledScope)
            ->whereNotNull('courier_id')
            ->select(
                'courier_id',
                DB::raw('count(*) as cancelled_count')
            )
            ->groupBy('courier_id')
            ->with('courier:id,name')
            ->orderByDesc('cancelled_count')
            ->get();

        $orderTotal = (clone $ordersScope)->count();

        $recentPerPage = (int) $request->input('recent_per_page', 15);
        if (! in_array($recentPerPage, [15, 30, 50], true)) {
            $recentPerPage = 15;
        }
        $recentSort = $request->filled('recent_sort') ? $request->string('recent_sort')->toString() : 'created_at_desc';
        $recentOrdersQuery = (clone $ordersScope)
            ->with(['customer', 'courier'])
            ->when($recentSort === 'created_at_asc', fn ($q) => $q->orderBy('created_at', 'asc'))
            ->when($recentSort === 'total_desc', fn ($q) => $q->orderBy('total_price', 'desc')->orderBy('created_at', 'desc'))
            ->when($recentSort === 'total_asc', fn ($q) => $q->orderBy('total_price', 'asc')->orderBy('created_at', 'desc'))
            ->when($recentSort === 'created_at_desc', fn ($q) => $q->orderBy('created_at', 'desc'));

        $recentOrders = $recentOrdersQuery
            ->paginate($recentPerPage, ['*'], 'recent_page')
            ->appends($request->query());

        return view('restaurant.reports', [
            'title' => 'Raporlar',
            'byStatus' => $byStatus,
            'orderTotal' => $orderTotal,
            'courierDeliveredByPayment' => $courierDeliveredByPayment,
            'deliveredCourierCountAllPayments' => $deliveredCourierCountAllPayments,
            'deliveredCount' => $deliveredCount,
            'cancelledCount' => $cancelledCount,
            'revenueDelivered' => $revenueDelivered,
            'deliveryFeesDelivered' => $deliveryFeesDelivered,
            'discountsDelivered' => $discountsDelivered,
            'packageFeesDelivered' => $packageFeesDelivered,
            'deliveredShopCount' => $deliveredShopCount,
            'deliveredShopRevenue' => $deliveredShopRevenue,
            'deliveredCourierCount' => $deliveredCourierCount,
            'courierDelivered' => $courierDelivered,
            'courierCancelled' => $courierCancelled,
            'recentOrders' => $recentOrders,
            'filters' => [
                'date_from' => $dateFrom?->format('Y-m-d'),
                'date_to' => $dateTo?->format('Y-m-d'),
                'preset' => $preset,
                'recent_per_page' => $recentPerPage,
                'recent_sort' => $recentSort,
            ],
        ]);
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function resolveDateRange(Request $request, ?string $preset): array
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse((string) $request->input('date_from')) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse((string) $request->input('date_to')) : null;

        if ($dateFrom !== null || $dateTo !== null) {
            return [$dateFrom, $dateTo];
        }

        $now = now();

        return match ($preset) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7d' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30d' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            default => [null, null],
        };
    }
}
