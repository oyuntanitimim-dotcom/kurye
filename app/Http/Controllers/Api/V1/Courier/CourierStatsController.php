<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Courier;

use App\Enums\CourierLedgerEntryKind;
use App\Enums\CourierLedgerEntryStatus;
use App\Enums\CourierPayoutSettlementStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Couriers\Models\CourierLedgerEntry;
use App\Modules\Couriers\Models\CourierPayoutSettlement;
use App\Modules\Orders\Models\Order;
use App\Support\FinanceReporting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierStatsController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $c = $request->user()?->courierProfile;
        if ($c === null) {
            abort(403);
        }

        $activeCount = Order::query()
            ->where('courier_id', $c->id)
            ->whereNotIn('status', [OrderStatus::Delivered->value, OrderStatus::Cancelled->value])
            ->count();

        $deliveredToday = Order::query()
            ->where('courier_id', $c->id)
            ->where('status', OrderStatus::Delivered->value)
            ->whereDate('updated_at', today());
        FinanceReporting::restrictToOnlinePayment($deliveredToday);

        $today = (clone $deliveredToday)->selectRaw('
            count(*) as delivered_count,
            coalesce(sum(total_price), 0) as revenue_total,
            coalesce(sum(courier_payout_amount), 0) as payout_total
        ')->first();

        return response()->json([
            'ok' => true,
            'active_orders' => (int) $activeCount,
            'today' => [
                'delivered_count' => (int) ($today?->delivered_count ?? 0),
                'revenue_total' => (float) ($today?->revenue_total ?? 0),
                'payout_total' => (float) ($today?->payout_total ?? 0),
            ],
        ]);
    }

    public function earnings(Request $request): JsonResponse
    {
        $c = $request->user()?->courierProfile;
        if ($c === null) {
            abort(403);
        }

        $period = $request->string('period', 'today')->toString();

        $q = Order::query()
            ->where('courier_id', $c->id)
            ->where('status', OrderStatus::Delivered->value);
        FinanceReporting::restrictToOnlinePayment($q);

        $label = 'Bugün';
        if ($period === 'week') {
            $q->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()]);
            $label = 'Bu hafta';
        } elseif ($period === 'month') {
            $q->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()]);
            $label = 'Bu ay';
        } else {
            $q->whereDate('updated_at', today());
            $period = 'today';
            $label = 'Bugün';
        }

        $summary = (clone $q)->selectRaw('
            count(*) as delivered_count,
            coalesce(sum(total_price), 0) as revenue_total,
            coalesce(sum(courier_payout_amount), 0) as payout_total
        ')->first();

        $daily = (clone $q)
            ->selectRaw('DATE(updated_at) as d, COALESCE(SUM(courier_payout_amount), 0) as payout, COUNT(*) as c')
            ->groupBy('d')
            ->orderByDesc('d')
            ->limit(14)
            ->get();

        return response()->json([
            'ok' => true,
            'period' => $period,
            'period_label' => $label,
            'delivered_count' => (int) ($summary?->delivered_count ?? 0),
            'revenue_total' => (float) ($summary?->revenue_total ?? 0),
            'payout_total' => (float) ($summary?->payout_total ?? 0),
            'daily' => $daily,
        ]);
    }

    /**
     * Kurye: henüz ödenmemiş hakediş, firma tarafı kapanışlarla toplam ödenen, son hareketler.
     */
    public function payoutBalance(Request $request): JsonResponse
    {
        $c = $request->user()?->courierProfile;
        if ($c === null) {
            abort(403);
        }

        $firmId = (int) $c->firm_id;
        $courierId = (int) $c->id;

        $unpaidOrders = Order::query()
            ->where('firm_id', $firmId)
            ->where('courier_id', $courierId)
            ->where('status', OrderStatus::Delivered->value)
            ->whereNull('courier_payout_settlement_id');
        FinanceReporting::restrictToOnlinePayment($unpaidOrders);

        $receivable_from_orders = (float) (clone $unpaidOrders)->sum('courier_payout_amount');

        $ledgers = CourierLedgerEntry::query()
            ->where('firm_id', $firmId)
            ->where('courier_id', $courierId)
            ->where('status', CourierLedgerEntryStatus::Open->value)
            ->get(['entry_kind', 'amount']);

        $ledgerD = 0.0;
        $ledgerC = 0.0;
        foreach ($ledgers as $row) {
            $kind = CourierLedgerEntryKind::tryFrom((string) $row->entry_kind);
            $a = (float) $row->amount;
            if ($kind === null) {
                continue;
            }
            if (in_array($kind, [CourierLedgerEntryKind::Advance, CourierLedgerEntryKind::Expense], true)) {
                $ledgerD += $a;
            } elseif ($kind === CourierLedgerEntryKind::Credit) {
                $ledgerC += $a;
            }
        }

        $receivable_net = $receivable_from_orders - $ledgerD + $ledgerC;

        $total_paid = (float) CourierPayoutSettlement::query()
            ->where('courier_id', $courierId)
            ->where('firm_id', $firmId)
            ->where('status', CourierPayoutSettlementStatus::Paid->value)
            ->sum('net_paid');

        $recent = CourierPayoutSettlement::query()
            ->where('courier_id', $courierId)
            ->where('firm_id', $firmId)
            ->where('status', CourierPayoutSettlementStatus::Paid->value)
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'net_paid', 'payment_method', 'payment_reference', 'period_start', 'period_end', 'created_at', 'earnings_from_orders', 'orders_count', 'ledger_deductions', 'ledger_credits']);

        $recentList = $recent->map(static function (CourierPayoutSettlement $s): array {
            return [
                'id' => (int) $s->id,
                'net_paid' => (float) $s->net_paid,
                'payment_method' => (string) $s->payment_method,
                'payment_reference' => $s->payment_reference,
                'period_start' => $s->period_start?->toDateString(),
                'period_end' => $s->period_end?->toDateString(),
                'created_at' => $s->created_at?->toIso8601String(),
                'earnings_from_orders' => (float) $s->earnings_from_orders,
                'orders_in_batch' => (int) $s->orders_count,
            ];
        })->all();

        return response()->json([
            'ok' => true,
            'receivable_from_unpaid_orders' => $receivable_from_orders,
            'open_ledger_deductions' => $ledgerD,
            'open_ledger_credits' => $ledgerC,
            'receivable_net' => $receivable_net,
            'total_paid_settlements' => $total_paid,
            'recent_payments' => $recentList,
        ]);
    }
}

