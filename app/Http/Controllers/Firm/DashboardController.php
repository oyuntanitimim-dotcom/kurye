<?php

namespace App\Http\Controllers\Firm;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Models\Firm;
use App\Modules\Firms\Services\FirmCreditService;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use App\Support\FinanceReporting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly FirmCreditService $firmCreditService) {}

    public function __invoke(): View
    {
        $firmId = Auth::user()->firm_id;
        $firm = Firm::query()->find($firmId);
        $creditBalance = (int) ($firm?->credit_balance ?? 0);
        $creditsPerOrder = $firm !== null ? $this->firmCreditService->creditsPerOrder($firm) : 1;

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $deliveredMonth = Order::query()
            ->where('firm_id', $firmId)
            ->where('status', OrderStatus::Delivered->value)
            ->whereBetween('updated_at', [$monthStart, $monthEnd]);
        FinanceReporting::restrictToOnlinePayment($deliveredMonth);

        $activeOrders = Order::query()
            ->where('firm_id', $firmId)
            ->whereNotIn('status', [OrderStatus::Delivered->value, OrderStatus::Cancelled->value]);

        return view('firm.dashboard', [
            'title' => 'Kurye şirketi paneli',
            'restaurantCount' => Restaurant::query()->where('firm_id', $firmId)->count(),
            'orderCount' => Order::query()->where('firm_id', $firmId)->count(),
            'activeOrdersCount' => (clone $activeOrders)->count(),
            'readyAwaitingCourierCount' => Order::query()
                ->where('firm_id', $firmId)
                ->readyForFirmCourierPool()
                ->count(),
            'activeCourierCount' => Courier::query()->where('firm_id', $firmId)->where('status', 'active')->count(),
            'creditBalance' => $creditBalance,
            'creditsPerOrder' => $creditsPerOrder,
            // Restoran "Hazır" demeden önceki aşamalar (beklemede / onay / hazırlık) firma özetinde gösterilmez.
            'recentOrders' => Order::query()
                ->where('firm_id', $firmId)
                ->whereNotIn('status', [
                    OrderStatus::Pending->value,
                    OrderStatus::Accepted->value,
                    OrderStatus::Preparing->value,
                ])
                ->with(['restaurant', 'customer'])
                ->latest()
                ->limit(10)
                ->get(),
            'financeThisMonth' => [
                'label' => $monthStart->copy()->locale((string) config('app.locale', 'tr'))->translatedFormat('F Y'),
                'delivered' => (clone $deliveredMonth)->count(),
                'revenue' => (float) (clone $deliveredMonth)->sum('total_price'),
                'platform' => (float) (clone $deliveredMonth)->sum('platform_fee_amount'),
                'commission' => (float) (clone $deliveredMonth)->sum('restaurant_commission_amount'),
                'courier' => (float) (clone $deliveredMonth)->sum('courier_payout_amount'),
            ],
        ]);
    }
}
