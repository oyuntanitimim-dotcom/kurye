<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Orders\Models\Order;
use App\Support\FinanceReporting;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $byStatus = Order::query()
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status');

        $deliveredStatus = OrderStatus::Delivered->value;

        $deliveredScope = Order::query()->where('status', $deliveredStatus);
        if ($request->filled('date_from')) {
            $deliveredScope->whereDate('updated_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $deliveredScope->whereDate('updated_at', '<=', $request->date('date_to'));
        }
        FinanceReporting::restrictToOnlinePayment($deliveredScope);

        $revenueDelivered = (float) (clone $deliveredScope)->sum('total_price');
        $deliveryFeesDelivered = (float) (clone $deliveredScope)->sum('delivery_fee');
        $discountsDelivered = (float) (clone $deliveredScope)->sum('discount_amount');
        $platformFeesDelivered = (float) (clone $deliveredScope)->sum('platform_fee_amount');
        $restaurantCommissionDelivered = (float) (clone $deliveredScope)->sum('restaurant_commission_amount');
        $courierPayoutDelivered = (float) (clone $deliveredScope)->sum('courier_payout_amount');

        $firmBreakdown = (clone $deliveredScope)
            ->select(
                'firm_id',
                DB::raw('count(*) as order_count'),
                DB::raw('sum(total_price) as revenue'),
                DB::raw('coalesce(sum(delivery_fee), 0) as delivery_fees'),
                DB::raw('coalesce(sum(discount_amount), 0) as discounts'),
                DB::raw('coalesce(sum(platform_fee_amount), 0) as platform_fees'),
                DB::raw('coalesce(sum(restaurant_commission_amount), 0) as restaurant_commission'),
                DB::raw('coalesce(sum(courier_payout_amount), 0) as courier_payout')
            )
            ->groupBy('firm_id')
            ->with('firm:id,name')
            ->orderByDesc('revenue')
            ->get();

        $courierBreakdown = (clone $deliveredScope)
            ->whereNotNull('courier_id')
            ->select(
                'firm_id',
                'courier_id',
                DB::raw('count(*) as delivered_count'),
                DB::raw('coalesce(sum(total_price), 0) as revenue'),
                DB::raw('coalesce(sum(platform_fee_amount), 0) as platform_fees'),
                DB::raw('coalesce(sum(restaurant_commission_amount), 0) as restaurant_commission'),
                DB::raw('coalesce(sum(courier_payout_amount), 0) as courier_payout'),
                DB::raw('coalesce(sum(discount_amount), 0) as discounts')
            )
            ->groupBy('firm_id', 'courier_id')
            ->with([
                'firm:id,name',
                'courier:id,name',
            ])
            ->orderByDesc('courier_payout')
            ->limit(50)
            ->get();

        return view('admin.reports', [
            'title' => 'Raporlar',
            'byStatus' => $byStatus,
            'orderTotal' => Order::query()->count(),
            'filters' => $request->only(['date_from', 'date_to']),
            'revenueDelivered' => $revenueDelivered,
            'deliveryFeesDelivered' => $deliveryFeesDelivered,
            'discountsDelivered' => $discountsDelivered,
            'platformFeesDelivered' => $platformFeesDelivered,
            'restaurantCommissionDelivered' => $restaurantCommissionDelivered,
            'courierPayoutDelivered' => $courierPayoutDelivered,
            'firmBreakdown' => $firmBreakdown,
            'courierBreakdown' => $courierBreakdown,
        ]);
    }
}
