<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Firm;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Support\FinanceReporting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceOverviewController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }

        $firmId = (int) $u->firm_id;
        [$from, $to, $preset] = $this->resolveDateRange($request);

        $q = Order::query()
            ->where('firm_id', $firmId)
            ->where('status', OrderStatus::Delivered->value);
        FinanceReporting::restrictToOnlinePayment($q);

        if ($from !== null) {
            $q->whereDate('updated_at', '>=', $from);
        }
        if ($to !== null) {
            $q->whereDate('updated_at', '<=', $to);
        }

        $summary = (clone $q)->selectRaw('
            count(*) as delivered_count,
            coalesce(sum(total_price), 0) as revenue_total,
            coalesce(sum(platform_fee_amount), 0) as platform_fees_total,
            coalesce(sum(restaurant_commission_amount), 0) as restaurant_commission_total,
            coalesce(sum(courier_payout_amount), 0) as courier_payout_total
        ')->first();

        return response()->json([
            'preset' => $preset,
            'date_from' => $from?->format('Y-m-d'),
            'date_to' => $to?->format('Y-m-d'),
            'delivered_count' => (int) ($summary?->delivered_count ?? 0),
            'revenue_total' => (float) ($summary?->revenue_total ?? 0),
            'platform_fees_total' => (float) ($summary?->platform_fees_total ?? 0),
            'restaurant_commission_total' => (float) ($summary?->restaurant_commission_total ?? 0),
            'courier_payout_total' => (float) ($summary?->courier_payout_total ?? 0),
        ]);
    }

    /**
     * @return array{0:?Carbon,1:?Carbon,2:?string}
     */
    private function resolveDateRange(Request $request): array
    {
        $preset = $request->filled('preset') ? $request->string('preset')->toString() : null;

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

