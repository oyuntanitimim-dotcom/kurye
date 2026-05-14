<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Firm;

use App\Enums\CourierPayoutPaymentMethod;
use App\Enums\CourierPayoutSettlementStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Couriers\Models\CourierLedgerEntry;
use App\Modules\Couriers\Models\CourierPayoutSettlement;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use App\Support\FinanceReporting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\CourierPayoutService;
use Throwable;

class FinanceModulesController extends Controller
{
    public function __construct(
        private readonly CourierPayoutService $payoutService
    ) {
    }

    public function balances(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }
        $firmId = (int) $u->firm_id;

        $rows = $this->deliveredScope($request, $firmId)
            ->select(
                'restaurant_id',
                DB::raw('count(*) as order_count'),
                DB::raw('coalesce(sum(total_price), 0) as revenue_total'),
                DB::raw('coalesce(sum(restaurant_commission_amount), 0) as commission_total')
            )
            ->groupBy('restaurant_id')
            ->with('restaurant:id,name')
            ->orderByDesc('commission_total')
            ->get()
            ->map(fn ($r): array => [
                'restaurant_id' => (int) $r->restaurant_id,
                'restaurant_name' => $r->restaurant?->name ?? '—',
                'order_count' => (int) $r->order_count,
                'revenue_total' => (float) $r->revenue_total,
                'commission_total' => (float) $r->commission_total,
            ])->values();

        $q = $this->deliveredScope($request, $firmId);
        $summary = (clone $q)->selectRaw('
            coalesce(sum(platform_fee_amount), 0) as platform_fees_total,
            coalesce(sum(restaurant_commission_amount), 0) as restaurant_commission_total
        ')->first();

        return response()->json([
            'rows' => $rows,
            'platform_fees_total' => (float) ($summary?->platform_fees_total ?? 0),
            'restaurant_commission_total' => (float) ($summary?->restaurant_commission_total ?? 0),
        ]);
    }

    public function courierCollections(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }
        $firmId = (int) $u->firm_id;

        $rows = $this->deliveredScope($request, $firmId)
            ->whereNotNull('courier_id')
            ->select(
                'courier_id',
                DB::raw('count(*) as order_count'),
                DB::raw('coalesce(sum(total_price), 0) as revenue_total'),
                DB::raw('coalesce(sum(courier_payout_amount), 0) as payout_total')
            )
            ->groupBy('courier_id')
            ->orderBy('courier_id')
            ->get();

        $courierNames = Courier::query()
            ->where('firm_id', $firmId)
            ->whereIn('id', $rows->pluck('courier_id')->filter())
            ->pluck('name', 'id');

        $mapped = $rows->map(fn ($r): array => [
            'courier_id' => (int) $r->courier_id,
            'courier_name' => (string) ($courierNames[$r->courier_id] ?? '—'),
            'order_count' => (int) $r->order_count,
            'revenue_total' => (float) $r->revenue_total,
            'payout_total' => (float) $r->payout_total,
        ])->values();

        return response()->json([
            'rows' => $mapped,
            'payout_grand' => (float) $rows->sum('payout_total'),
        ]);
    }

    public function courierCollectionDetail(Request $request, Courier $courier): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }
        if ((int) $courier->firm_id !== (int) $u->firm_id) {
            abort(403);
        }

        $firmId = (int) $u->firm_id;
        $q = $this->deliveredScope($request, $firmId)
            ->where('courier_id', $courier->id);

        $orders = (clone $q)->selectRaw('
            count(*) as order_count,
            coalesce(sum(total_price), 0) as revenue_total,
            coalesce(sum(courier_payout_amount), 0) as payout_total
        ')->first();

        $ledgerQ = CourierLedgerEntry::query()
            ->where('firm_id', $firmId)
            ->where('courier_id', $courier->id);
        if ($request->filled('date_from')) {
            $ledgerQ->whereDate('entry_date', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $ledgerQ->whereDate('entry_date', '<=', $request->date('date_to'));
        }

        $ledgerTotals = (clone $ledgerQ)->selectRaw("
            coalesce(sum(case when entry_kind='advance' then amount else 0 end), 0) as advance_total,
            coalesce(sum(case when entry_kind='expense' then amount else 0 end), 0) as expense_total,
            coalesce(sum(case when entry_kind='credit' then amount else 0 end), 0) as credit_total,
            coalesce(sum(case when status='open' and entry_kind='advance' then amount else 0 end), 0) as open_advance,
            coalesce(sum(case when status='open' and entry_kind='expense' then amount else 0 end), 0) as open_expense,
            coalesce(sum(case when status='open' and entry_kind='credit' then amount else 0 end), 0) as open_credit
        ")->first();

        $settlementsPaid = CourierPayoutSettlement::query()
            ->where('firm_id', $firmId)
            ->where('courier_id', $courier->id)
            ->where('status', CourierPayoutSettlementStatus::Paid->value);
        if ($request->filled('date_from')) {
            $settlementsPaid->whereDate('created_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $settlementsPaid->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $paidTotal = (float) $settlementsPaid->sum('net_paid');
        $payoutTotal = (float) ($orders?->payout_total ?? 0);
        $openDelta = (float) (($ledgerTotals?->open_credit ?? 0) - ($ledgerTotals?->open_advance ?? 0) - ($ledgerTotals?->open_expense ?? 0));
        $netBalance = $payoutTotal + $openDelta - $paidTotal;

        return response()->json([
            'courier' => [
                'id' => $courier->id,
                'name' => $courier->name,
                'phone' => $courier->phone,
                'status' => $courier->status,
            ],
            'orders' => [
                'count' => (int) ($orders?->order_count ?? 0),
                'revenue_total' => (float) ($orders?->revenue_total ?? 0),
                'payout_total' => $payoutTotal,
            ],
            'ledger' => [
                'advance_total' => (float) ($ledgerTotals?->advance_total ?? 0),
                'expense_total' => (float) ($ledgerTotals?->expense_total ?? 0),
                'credit_total' => (float) ($ledgerTotals?->credit_total ?? 0),
                'open_advance' => (float) ($ledgerTotals?->open_advance ?? 0),
                'open_expense' => (float) ($ledgerTotals?->open_expense ?? 0),
                'open_credit' => (float) ($ledgerTotals?->open_credit ?? 0),
            ],
            'settlements' => [
                'paid_total' => $paidTotal,
            ],
            'balance' => [
                'net' => $netBalance,
                'courier_receivable' => $netBalance > 0 ? $netBalance : 0.0,
                'courier_payable' => $netBalance < 0 ? abs($netBalance) : 0.0,
            ],
        ]);
    }

    public function courierPayouts(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }
        $firmId = (int) $u->firm_id;

        $q = CourierPayoutSettlement::query()
            ->where('firm_id', $firmId)
            ->with(['courier:id,name'])
            ->orderByDesc('id');

        if ($request->filled('courier_id')) {
            $cid = (int) $request->input('courier_id');
            if ($cid > 0) {
                $q->where('courier_id', $cid);
            }
        }

        $items = $q->paginate(20);
        $items->getCollection()->transform(fn (CourierPayoutSettlement $s): array => [
            'id' => $s->id,
            'courier_id' => $s->courier_id,
            'courier_name' => $s->courier?->name ?? '—',
            'period_start' => $s->period_start?->format('Y-m-d'),
            'period_end' => $s->period_end?->format('Y-m-d'),
            'orders_count' => (int) $s->orders_count,
            'net_paid' => (float) $s->net_paid,
            'payment_method' => (string) $s->payment_method,
            'status' => (string) $s->status,
        ]);

        return response()->json($items);
    }

    public function courierPayoutShow(Request $request, CourierPayoutSettlement $settlement): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }
        if ((int) $settlement->firm_id !== (int) $u->firm_id) {
            abort(403);
        }

        $settlement->load(['courier:id,name,phone', 'recordedBy:id,name', 'orders' => function ($q): void {
            $q->orderBy('updated_at');
        }]);
        $settlement->loadCount('orders');
        $ledger = $settlement->ledgerEntries()->orderBy('entry_date')->orderBy('id')->get();

        return response()->json([
            'id' => $settlement->id,
            'status' => $settlement->status,
            'courier' => [
                'id' => $settlement->courier?->id,
                'name' => $settlement->courier?->name,
                'phone' => $settlement->courier?->phone,
            ],
            'recorded_by' => [
                'id' => $settlement->recordedBy?->id,
                'name' => $settlement->recordedBy?->name,
            ],
            'period_start' => $settlement->period_start?->format('Y-m-d'),
            'period_end' => $settlement->period_end?->format('Y-m-d'),
            'period_preset' => $settlement->period_preset,
            'orders_count' => (int) $settlement->orders_count,
            'earnings_from_orders' => (float) $settlement->earnings_from_orders,
            'ledger_deductions' => (float) $settlement->ledger_deductions,
            'ledger_credits' => (float) $settlement->ledger_credits,
            'net_paid' => (float) $settlement->net_paid,
            'payment_method' => $settlement->payment_method,
            'payment_reference' => $settlement->payment_reference,
            'notes' => $settlement->notes,
            'orders' => $settlement->orders->map(fn (Order $o): array => [
                'id' => $o->id,
                'updated_at' => $o->updated_at?->toIso8601String(),
                'total_price' => (float) $o->total_price,
                'courier_payout_amount' => (float) $o->courier_payout_amount,
            ])->values(),
            'ledger' => $ledger->map(fn ($l): array => [
                'id' => $l->id,
                'entry_date' => $l->entry_date?->format('Y-m-d'),
                'entry_kind' => $l->entry_kind,
                'amount' => (float) $l->amount,
                'status' => $l->status,
                'note' => $l->note,
            ])->values(),
        ]);
    }

    public function courierPayoutCreate(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }
        $firmId = (int) $u->firm_id;

        $payload = $request->validate([
            'courier_id' => ['required', 'integer', 'min:1'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'payment_method' => ['required', 'string'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'include_all_ledger' => ['nullable', 'boolean'],
            'orders_all_time' => ['nullable', 'boolean'],
            'period_preset' => ['nullable', 'string', 'max:32'],
        ]);

        $method = CourierPayoutPaymentMethod::tryFrom((string) $payload['payment_method']);
        if ($method === null) {
            return response()->json(['message' => 'Geçerli bir ödeme yöntemi seçiniz.'], 422);
        }

        try {
            $settlement = $this->payoutService->createSettlement(
                $firmId,
                (int) $payload['courier_id'],
                (int) $u->id,
                Carbon::parse((string) $payload['date_from']),
                Carbon::parse((string) $payload['date_to']),
                isset($payload['period_preset']) ? (string) $payload['period_preset'] : null,
                $method,
                isset($payload['payment_reference']) ? (string) $payload['payment_reference'] : null,
                isset($payload['notes']) ? (string) $payload['notes'] : null,
                (bool) ($payload['include_all_ledger'] ?? false),
                (bool) ($payload['orders_all_time'] ?? false)
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'settlement_id' => $settlement->id,
        ], 201);
    }

    public function courierPayoutVoid(Request $request, CourierPayoutSettlement $settlement): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }
        if ((int) $settlement->firm_id !== (int) $u->firm_id) {
            abort(403);
        }
        if ($settlement->status === CourierPayoutSettlementStatus::Voided->value) {
            return response()->json(['ok' => true]);
        }

        $this->payoutService->voidSettlement($settlement);

        return response()->json(['ok' => true]);
    }

    public function reconciliation(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }
        $firmId = (int) $u->firm_id;

        $month = $request->filled('month')
            ? $request->string('month')->toString()
            : now()->format('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $q = Order::query()
            ->where('firm_id', $firmId)
            ->where('status', OrderStatus::Delivered->value)
            ->whereBetween('updated_at', [$start, $end]);
        FinanceReporting::restrictToOnlinePayment($q);

        $daily = (clone $q)
            ->select(
                DB::raw('DATE(updated_at) as d'),
                DB::raw('count(*) as c'),
                DB::raw('coalesce(sum(total_price), 0) as revenue'),
                DB::raw('coalesce(sum(platform_fee_amount), 0) as platform_fees'),
                DB::raw('coalesce(sum(restaurant_commission_amount), 0) as commission'),
                DB::raw('coalesce(sum(courier_payout_amount), 0) as courier_payout')
            )
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->map(fn ($r): array => [
                'date' => (string) $r->d,
                'count' => (int) $r->c,
                'revenue' => (float) $r->revenue,
                'platform_fees' => (float) $r->platform_fees,
                'commission' => (float) $r->commission,
                'courier_payout' => (float) $r->courier_payout,
            ])->values();

        return response()->json([
            'month' => $month,
            'delivered_count' => (clone $q)->count(),
            'revenue_total' => (float) (clone $q)->sum('total_price'),
            'platform_fees_total' => (float) (clone $q)->sum('platform_fee_amount'),
            'restaurant_commission_total' => (float) (clone $q)->sum('restaurant_commission_amount'),
            'courier_payout_total' => (float) (clone $q)->sum('courier_payout_amount'),
            'daily' => $daily,
        ]);
    }

    private function deliveredScope(Request $request, int $firmId): Builder
    {
        $q = Order::query()
            ->where('firm_id', $firmId)
            ->where('status', OrderStatus::Delivered->value);
        FinanceReporting::restrictToOnlinePayment($q);

        if ($request->filled('restaurant_id')) {
            $restaurantId = (int) $request->input('restaurant_id');
            if ($restaurantId > 0 && Restaurant::query()->where('firm_id', $firmId)->whereKey($restaurantId)->exists()) {
                $q->where('restaurant_id', $restaurantId);
            }
        }

        if ($request->filled('courier_id')) {
            $courierId = (int) $request->input('courier_id');
            if ($courierId > 0 && Courier::query()->where('firm_id', $firmId)->whereKey($courierId)->exists()) {
                $q->where('courier_id', $courierId);
            }
        }

        if ($request->filled('date_from')) {
            $q->whereDate('updated_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('updated_at', '<=', $request->date('date_to'));
        }

        return $q;
    }
}

