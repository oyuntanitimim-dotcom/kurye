<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Support\FinanceReporting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $courier = Auth::user()->courierProfile;
        if ($courier === null) {
            abort(403);
        }

        $period = $request->string('period', 'today')->toString();

        $query = Order::query()
            ->where('courier_id', $courier->id)
            ->where('status', 'delivered');
        FinanceReporting::restrictToOnlinePayment($query);

        $label = 'Bugün';

        if ($period === 'week') {
            $query->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()]);
            $label = 'Bu hafta';
        } elseif ($period === 'month') {
            $query->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()]);
            $label = 'Bu ay';
        } elseif ($period === 'custom' && $request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('updated_at', [
                $request->date('date_from')->startOfDay(),
                $request->date('date_to')->endOfDay(),
            ]);
            $label = 'Seçilen aralık';
        } else {
            if ($period === 'custom') {
                $period = 'today';
            }
            $query->whereDate('updated_at', today());
            $label = 'Bugün';
        }

        $total = (float) (clone $query)->sum('total_price');
        $count = (clone $query)->count();
        $courierPayoutTotal = (float) (clone $query)->sum('courier_payout_amount');

        $dailyBreakdown = (clone $query)
            ->selectRaw('DATE(updated_at) as d, SUM(total_price) as revenue, COALESCE(SUM(courier_payout_amount), 0) as payout, COUNT(*) as c')
            ->groupBy('d')
            ->orderByDesc('d')
            ->limit(14)
            ->get();

        return view('courier.earnings', [
            'title' => 'Kazançlar',
            'total' => $total,
            'courierPayoutTotal' => $courierPayoutTotal,
            'count' => $count,
            'period' => $period,
            'periodLabel' => $label,
            'dailyBreakdown' => $dailyBreakdown,
            'filters' => $request->only(['period', 'date_from', 'date_to']),
        ]);
    }
}
