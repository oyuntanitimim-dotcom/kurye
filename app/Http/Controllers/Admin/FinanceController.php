<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Support\FinanceReporting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    public function index(Request $request): View
    {
        return $this->overview($request);
    }

    public function overview(Request $request): View
    {
        $q = $this->deliveredScope($request);

        return view('admin.finance.overview', [
            'title' => 'Finans — Genel durum',
            'filters' => $request->only(['date_from', 'date_to']),
            'deliveredCount' => (clone $q)->count(),
            'revenueTotal' => (float) (clone $q)->sum('total_price'),
            'platformFeesTotal' => (float) (clone $q)->sum('platform_fee_amount'),
            'restaurantCommissionTotal' => (float) (clone $q)->sum('restaurant_commission_amount'),
            'courierPayoutTotal' => (float) (clone $q)->sum('courier_payout_amount'),
        ]);
    }

    public function firms(Request $request): View
    {
        $firmBreakdown = $this->deliveredScope($request)
            ->whereNotNull('firm_id')
            ->select(
                'firm_id',
                DB::raw('count(*) as order_count'),
                DB::raw('coalesce(sum(total_price), 0) as revenue'),
                DB::raw('coalesce(sum(platform_fee_amount), 0) as platform_fees'),
                DB::raw('coalesce(sum(restaurant_commission_amount), 0) as restaurant_commission'),
                DB::raw('coalesce(sum(courier_payout_amount), 0) as courier_payout')
            )
            ->groupBy('firm_id')
            ->with('firm:id,name')
            ->orderByDesc('platform_fees')
            ->get();

        return view('admin.finance.firms', [
            'title' => 'Finans — Şirket ciroları',
            'filters' => $request->only(['date_from', 'date_to']),
            'firmBreakdown' => $firmBreakdown,
        ]);
    }

    public function reconciliation(Request $request): View|StreamedResponse
    {
        $month = $request->filled('month')
            ? $request->string('month')->toString()
            : now()->format('Y-m');

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $q = Order::query()
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
            $filename = 'platform-mutabakat-'.$month.'.csv';

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

        return view('admin.finance.reconciliation', [
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

    private function deliveredScope(Request $request): Builder
    {
        $q = Order::query()
            ->where('status', OrderStatus::Delivered->value);
        FinanceReporting::restrictToOnlinePayment($q);

        if ($request->filled('date_from')) {
            $q->whereDate('updated_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('updated_at', '<=', $request->date('date_to'));
        }

        return $q;
    }
}
