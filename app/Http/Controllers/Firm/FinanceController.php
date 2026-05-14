<?php

declare(strict_types=1);

namespace App\Http\Controllers\Firm;

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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    public function overview(Request $request): View
    {
        $firmId = (int) Auth::user()->firm_id;
        $q = $this->deliveredScope($request, $firmId);

        $summary = (clone $q)->selectRaw('
            count(*) as delivered_count,
            coalesce(sum(total_price), 0) as revenue_total,
            coalesce(sum(platform_fee_amount), 0) as platform_fees_total,
            coalesce(sum(restaurant_commission_amount), 0) as restaurant_commission_total,
            coalesce(sum(courier_payout_amount), 0) as courier_payout_total
        ')->first();

        return view('firm.finance.overview', [
            'title' => 'Finans — Genel durum',
            'filters' => $request->only(['date_from', 'date_to', 'restaurant_id']),
            'restaurants' => Restaurant::query()->where('firm_id', $firmId)->orderBy('name')->get(['id', 'name']),
            'deliveredCount' => (int) ($summary?->delivered_count ?? 0),
            'revenueTotal' => (float) ($summary?->revenue_total ?? 0),
            'platformFeesTotal' => (float) ($summary?->platform_fees_total ?? 0),
            'restaurantCommissionTotal' => (float) ($summary?->restaurant_commission_total ?? 0),
            'courierPayoutTotal' => (float) ($summary?->courier_payout_total ?? 0),
        ]);
    }

    public function balances(Request $request): View
    {
        $firmId = (int) Auth::user()->firm_id;
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
            ->get();

        $q = $this->deliveredScope($request, $firmId);
        $summary = (clone $q)->selectRaw('
            coalesce(sum(platform_fee_amount), 0) as platform_fees_total,
            coalesce(sum(restaurant_commission_amount), 0) as restaurant_commission_total
        ')->first();

        return view('firm.finance.balances', [
            'title' => 'Finans — Borç / alacak listesi',
            'filters' => $request->only(['date_from', 'date_to', 'restaurant_id']),
            'rows' => $rows,
            'platformFeesTotal' => (float) ($summary?->platform_fees_total ?? 0),
            'restaurantCommissionTotal' => (float) ($summary?->restaurant_commission_total ?? 0),
            'restaurants' => Restaurant::query()->where('firm_id', $firmId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function courierCollections(Request $request): View
    {
        $firmId = (int) Auth::user()->firm_id;
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

        return view('firm.finance.courier_collections', [
            'title' => 'Finans — Kurye ücret özeti',
            'filters' => $request->only(['date_from', 'date_to', 'courier_id']),
            'rows' => $rows,
            'courierNames' => $courierNames,
            'payoutGrand' => (float) $rows->sum('payout_total'),
            'couriers' => Courier::query()->where('firm_id', $firmId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function courierCollectionDetail(Request $request, Courier $courier): JsonResponse
    {
        $firmId = (int) Auth::user()->firm_id;
        if ((int) $courier->firm_id !== $firmId) {
            abort(403);
        }

        $q = $this->deliveredScope($request, $firmId)->where('courier_id', $courier->id);
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
            ->where('status', 'paid');
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
            'settlements' => ['paid_total' => $paidTotal],
            'balance' => [
                'net' => $netBalance,
                'courier_receivable' => $netBalance > 0 ? $netBalance : 0.0,
                'courier_payable' => $netBalance < 0 ? abs($netBalance) : 0.0,
            ],
        ]);
    }

    public function reconciliation(Request $request): View|StreamedResponse
    {
        $firmId = (int) Auth::user()->firm_id;
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
            ->get();

        $monthLabel = $start->copy()->locale((string) config('app.locale', 'tr'))->translatedFormat('F Y');

        $deliveredCount = (clone $q)->count();
        $revenueTotal = (float) (clone $q)->sum('total_price');
        $platformFeesTotal = (float) (clone $q)->sum('platform_fee_amount');
        $restaurantCommissionTotal = (float) (clone $q)->sum('restaurant_commission_amount');
        $courierPayoutTotal = (float) (clone $q)->sum('courier_payout_amount');

        if ($request->query('export') === 'csv') {
            $filename = 'mutabakat-'.$firmId.'-'.$month.'.csv';

            return response()->streamDownload(function () use ($daily, $deliveredCount, $revenueTotal, $platformFeesTotal, $restaurantCommissionTotal, $courierPayoutTotal): void {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['gun', 'teslim_adet', 'ciro', 'platform', 'isletme_paket', 'kurye'], ';');
                foreach ($daily as $row) {
                    fputcsv($out, [
                        (string) $row->d,
                        (string) $row->c,
                        number_format((float) $row->revenue, 2, '.', ''),
                        number_format((float) $row->platform_fees, 2, '.', ''),
                        number_format((float) $row->commission, 2, '.', ''),
                        number_format((float) $row->courier_payout, 2, '.', ''),
                    ], ';');
                }
                fputcsv($out, [
                    'TOPLAM',
                    (string) $deliveredCount,
                    number_format($revenueTotal, 2, '.', ''),
                    number_format($platformFeesTotal, 2, '.', ''),
                    number_format($restaurantCommissionTotal, 2, '.', ''),
                    number_format($courierPayoutTotal, 2, '.', ''),
                ], ';');
                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        return view('firm.finance.reconciliation', [
            'title' => 'Finans — Mutabakat',
            'month' => $month,
            'monthLabel' => $monthLabel,
            'deliveredCount' => $deliveredCount,
            'revenueTotal' => $revenueTotal,
            'platformFeesTotal' => $platformFeesTotal,
            'restaurantCommissionTotal' => $restaurantCommissionTotal,
            'courierPayoutTotal' => $courierPayoutTotal,
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
