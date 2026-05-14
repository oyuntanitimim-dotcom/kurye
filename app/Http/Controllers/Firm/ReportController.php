<?php

namespace App\Http\Controllers\Firm;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use App\Support\FirmReportInsights;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $firmId = (int) Auth::user()->firm_id;
        $restaurantId = $request->filled('restaurant_id') ? (int) $request->input('restaurant_id') : null;
        if ($restaurantId !== null && $restaurantId > 0) {
            $ok = Restaurant::query()->where('firm_id', $firmId)->whereKey($restaurantId)->exists();
            if (! $ok) {
                $restaurantId = null;
            }
        }

        $preset = $request->filled('preset') ? $request->string('preset')->toString() : null;
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, $preset);

        $insights = FirmReportInsights::forFirmScope($firmId, $restaurantId, $dateFrom, $dateTo);

        $byStatus = $insights['byStatus'];
        $orderTotal = $insights['orderTotal'];
        $courierDeliveredByPayment = $insights['courierDeliveredByPayment'];
        $revenueDelivered = $insights['revenueDelivered'];
        $deliveryFeesDelivered = $insights['deliveryFeesDelivered'];
        $discountsDelivered = $insights['discountsDelivered'];
        $platformFeesDelivered = $insights['platformFeesDelivered'];
        $restaurantCommissionDelivered = $insights['restaurantCommissionDelivered'];
        $courierPayoutsDelivered = $insights['courierPayoutsDelivered'];
        $deliveredShopCount = $insights['deliveredShopCount'];
        $deliveredShopRevenue = $insights['deliveredShopRevenue'];
        $courierBreakdown = $insights['courierBreakdown'];
        $topRestaurantsByRevenue = $insights['topRestaurantsByRevenue'];
        $topCategoriesByRevenue = $insights['topCategoriesByRevenue'];
        $topProductsByRevenue = $insights['topProductsByRevenue'];

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

        $recentPerPage = (int) $request->input('recent_per_page', 15);
        if (! in_array($recentPerPage, [15, 30, 50], true)) {
            $recentPerPage = 15;
        }

        $recentSort = $request->filled('recent_sort') ? $request->string('recent_sort')->toString() : 'created_at_desc';
        $recentOrdersQuery = (clone $ordersScope)
            ->with(['restaurant', 'customer'])
            ->when($recentSort === 'created_at_asc', fn ($q) => $q->orderBy('created_at', 'asc'))
            ->when($recentSort === 'total_desc', fn ($q) => $q->orderBy('total_price', 'desc')->orderBy('created_at', 'desc'))
            ->when($recentSort === 'total_asc', fn ($q) => $q->orderBy('total_price', 'asc')->orderBy('created_at', 'desc'))
            ->when($recentSort === 'created_at_desc', fn ($q) => $q->orderBy('created_at', 'desc'));

        $recentOrders = $recentOrdersQuery
            ->paginate($recentPerPage, ['*'], 'recent_page')
            ->appends($request->query());

        return view('firm.reports', [
            'title' => 'Raporlar',
            'byStatus' => $byStatus,
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
            'recentOrders' => $recentOrders,
            'filters' => [
                'date_from' => $dateFrom?->format('Y-m-d'),
                'date_to' => $dateTo?->format('Y-m-d'),
                'preset' => $preset,
                'restaurant_id' => $restaurantId,
                'recent_per_page' => $recentPerPage,
                'recent_sort' => $recentSort,
            ],
            'restaurants' => Restaurant::query()->where('firm_id', $firmId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * date_from/date_to doluysa onu kullanır. Boşsa preset'e göre aralık döner.
     *
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
