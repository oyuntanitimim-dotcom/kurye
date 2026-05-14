<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Firm;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use App\Support\FirmReportInsights;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportsSummaryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }

        $firmId = (int) $u->firm_id;

        $restaurantId = $request->filled('restaurant_id') ? (int) $request->input('restaurant_id') : null;
        if ($restaurantId !== null && $restaurantId > 0) {
            $ok = Restaurant::query()->where('firm_id', $firmId)->whereKey($restaurantId)->exists();
            if (! $ok) {
                $restaurantId = null;
            }
        }

        $preset = $request->filled('preset') ? $request->string('preset')->toString() : null;
        [$from, $to, $resolvedPreset] = $this->resolveDateRange($request, $preset);

        $insights = FirmReportInsights::forFirmScope($firmId, $restaurantId, $from, $to);

        $fcb = $insights['courierDeliveredByPayment'];

        $revenueDelivered = $insights['revenueDelivered'];
        $platformFeesDelivered = $insights['platformFeesDelivered'];
        $restaurantCommissionDelivered = $insights['restaurantCommissionDelivered'];
        $courierPayoutsDelivered = $insights['courierPayoutsDelivered'];
        $net = $revenueDelivered - $platformFeesDelivered - $restaurantCommissionDelivered - $courierPayoutsDelivered;

        $days = (int) $request->integer('series_days', 14);
        $days = max(7, min(60, $days));

        $seriesFrom = ($to ?? now())->copy()->subDays($days - 1)->startOfDay();
        $seriesTo = ($to ?? now())->copy()->endOfDay();

        $delivered = OrderStatus::Delivered->value;

        $daily = Order::query()
            ->where('firm_id', $firmId)
            ->where('status', $delivered)
            ->when($restaurantId !== null && $restaurantId > 0, fn ($q) => $q->where('restaurant_id', $restaurantId))
            ->whereBetween('updated_at', [$seriesFrom, $seriesTo])
            ->selectRaw('DATE(updated_at) as d, count(*) as c, coalesce(sum(total_price), 0) as revenue')
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->map(fn ($r) => ['d' => (string) $r->d, 'c' => (int) $r->c, 'revenue' => (float) $r->revenue])
            ->values();

        $byStatusKeyed = [];
        foreach ($insights['byStatus'] as $status => $count) {
            $byStatusKeyed[(string) $status] = (int) $count;
        }

        $courierBreakdown = $insights['courierBreakdown']->map(function ($row): array {
            $rev = (float) $row->revenue;
            $pf = (float) $row->platform_fees;
            $rc = (float) $row->restaurant_commission;
            $cp = (float) $row->courier_payout;

            return [
                'courier_id' => (int) $row->courier_id,
                'courier_name' => $row->courier?->name ?? '—',
                'delivered_count' => (int) $row->delivered_count,
                'revenue' => $rev,
                'discounts' => (float) $row->discounts,
                'delivery_fees' => (float) $row->delivery_fees,
                'platform_fees' => $pf,
                'restaurant_commission' => $rc,
                'courier_payout' => $cp,
                'net' => $rev - $pf - $rc - $cp,
            ];
        })->values();

        $topRestaurants = $insights['topRestaurantsByRevenue']->map(fn ($r): array => [
            'restaurant_id' => (int) ($r->restaurant_id ?? 0),
            'restaurant_name' => (string) $r->restaurant_name,
            'qty' => (int) $r->qty,
            'revenue' => (float) $r->revenue,
        ])->values();

        $topCategories = $insights['topCategoriesByRevenue']->map(fn ($r): array => [
            'category_id' => $r->category_id !== null ? (int) $r->category_id : null,
            'category_name' => (string) $r->category_name,
            'qty' => (int) $r->qty,
            'revenue' => (float) $r->revenue,
        ])->values();

        $topProducts = $insights['topProductsByRevenue']->map(fn ($r): array => [
            'product_id' => $r->product_id !== null ? (int) $r->product_id : null,
            'product_name' => (string) $r->product_name,
            'qty' => (int) $r->qty,
            'revenue' => (float) $r->revenue,
        ])->values();

        $courierRevSum =
            (float) ($fcb?->rev_online ?? 0)
            + (float) ($fcb?->rev_card ?? 0)
            + (float) ($fcb?->rev_cash ?? 0)
            + (float) ($fcb?->rev_other ?? 0);
        $shopRev = (float) $insights['deliveredShopRevenue'];
        $breakdownTotal = $courierRevSum + $shopRev;
        $reconOk = abs($breakdownTotal - $revenueDelivered) < 0.05;

        $restaurants = Restaurant::query()
            ->where('firm_id', $firmId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'preset' => $resolvedPreset,
            'date_from' => $from?->format('Y-m-d'),
            'date_to' => $to?->format('Y-m-d'),
            'restaurant_id' => $restaurantId,
            'restaurants' => $restaurants->map(fn (Restaurant $r): array => [
                'id' => $r->id,
                'name' => $r->name,
            ]),
            'order_total_filtered' => $insights['orderTotal'],
            'by_status' => $byStatusKeyed,
            'delivered' => [
                'count' => $insights['deliveredOrderCount'],
                'revenue' => $revenueDelivered,
                'delivery_fees' => $insights['deliveryFeesDelivered'],
                'discounts' => $insights['discountsDelivered'],
                'platform_fees' => $platformFeesDelivered,
                'restaurant_commission' => $restaurantCommissionDelivered,
                'courier_payouts' => $courierPayoutsDelivered,
                'net' => $net,
                'delivered_shop_count' => $insights['deliveredShopCount'],
                'delivered_shop_revenue' => $shopRev,
            ],
            'courier_delivered_by_payment' => [
                'online' => [
                    'count' => (int) ($fcb?->cnt_online ?? 0),
                    'revenue' => (float) ($fcb?->rev_online ?? 0),
                ],
                'card' => [
                    'count' => (int) ($fcb?->cnt_card ?? 0),
                    'revenue' => (float) ($fcb?->rev_card ?? 0),
                ],
                'cash' => [
                    'count' => (int) ($fcb?->cnt_cash ?? 0),
                    'revenue' => (float) ($fcb?->rev_cash ?? 0),
                ],
                'other' => [
                    'count' => (int) ($fcb?->cnt_other ?? 0),
                    'revenue' => (float) ($fcb?->rev_other ?? 0),
                ],
            ],
            'payment_breakdown_reconciliation' => [
                'courier_payment_methods_total' => $courierRevSum,
                'shop_delivery_revenue' => $shopRev,
                'breakdown_total' => $breakdownTotal,
                'delivered_revenue' => $revenueDelivered,
                'matched' => $reconOk,
            ],
            'courier_breakdown' => $courierBreakdown,
            'restaurants_by_revenue' => $topRestaurants,
            'categories_by_revenue' => $topCategories,
            'products_by_revenue' => $topProducts,
            'daily' => $daily,
        ]);
    }

    /**
     * Web /firma/raporlar ile aynı: date_from/date_to doluysa preset yok sayılır.
     *
     * @return array{0:?Carbon,1:?Carbon,2:?string}
     */
    private function resolveDateRange(Request $request, ?string $preset): array
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse((string) $request->input('date_from')) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse((string) $request->input('date_to')) : null;

        if ($dateFrom !== null || $dateTo !== null) {
            return [$dateFrom, $dateTo, $preset];
        }

        $now = now();
        $range = match ($preset) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7d' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30d' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            default => [null, null],
        };

        return [$range[0], $range[1], $preset];
    }
}
