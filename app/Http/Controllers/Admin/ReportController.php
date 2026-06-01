<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Firms\Models\Firm;
use App\Modules\Firms\Models\FirmCreditPurchase;
use App\Modules\Firms\Models\FirmCreditTransaction;
use App\Modules\Firms\Services\FirmCreditService;
use App\Modules\Orders\Models\Order;
use App\Support\FinanceReporting;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly FirmCreditService $firmCreditService) {}

    public function __invoke(Request $request): View
    {
        $credit = $this->creditReport($request);

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
            'credit' => $credit,
        ]);
    }

    /**
     * Admin kontör (kredi) raporu: satılan / yüklenen / tüketilen kontör ve admin geliri.
     *
     * @return array<string, mixed>
     */
    private function creditReport(Request $request): array
    {
        $from = $request->filled('date_from') ? $request->date('date_from') : null;
        $to = $request->filled('date_to') ? $request->date('date_to') : null;

        // Onaylanan satışlar = admin geliri (approved_at tarihine göre)
        $sold = FirmCreditPurchase::query()->where('status', FirmCreditPurchase::STATUS_APPROVED);
        if ($from !== null) {
            $sold->whereDate('approved_at', '>=', $from);
        }
        if ($to !== null) {
            $sold->whereDate('approved_at', '<=', $to);
        }

        $soldCredits = (int) (clone $sold)->sum('credits');
        $soldRevenue = (float) (clone $sold)->sum('total_price');
        $soldCount = (int) (clone $sold)->count();

        $soldByFirm = (clone $sold)
            ->select('firm_id',
                DB::raw('sum(credits) as credits'),
                DB::raw('sum(total_price) as revenue'),
                DB::raw('count(*) as cnt'))
            ->groupBy('firm_id')
            ->get()
            ->keyBy('firm_id');

        // Bekleyen talepler
        $pending = FirmCreditPurchase::query()->where('status', FirmCreditPurchase::STATUS_PENDING);
        $pendingCount = (int) (clone $pending)->count();
        $pendingCredits = (int) (clone $pending)->sum('credits');
        $pendingRevenue = (float) (clone $pending)->sum('total_price');

        // Kontör hareketleri (created_at tarihine göre)
        $tx = FirmCreditTransaction::query();
        if ($from !== null) {
            $tx->whereDate('created_at', '>=', $from);
        }
        if ($to !== null) {
            $tx->whereDate('created_at', '<=', $to);
        }

        $loaded = (int) (clone $tx)->where('amount', '>', 0)->sum('amount');
        $consumed = (int) abs((int) (clone $tx)->where('type', FirmCreditTransaction::TYPE_ORDER_DEDUCTION)->sum('amount'));
        $adminAdded = (int) (clone $tx)->where('type', FirmCreditTransaction::TYPE_ADMIN_ADJUSTMENT)->sum('amount');
        $adminAddedPositive = (int) (clone $tx)
            ->where('type', FirmCreditTransaction::TYPE_ADMIN_ADJUSTMENT)
            ->where('amount', '>', 0)
            ->sum('amount');

        $consumedByFirm = (clone $tx)
            ->where('type', FirmCreditTransaction::TYPE_ORDER_DEDUCTION)
            ->select('firm_id',
                DB::raw('sum(amount) as amount'),
                DB::raw('count(*) as cnt'))
            ->groupBy('firm_id')
            ->get()
            ->keyBy('firm_id');

        $adminAddedByFirm = (clone $tx)
            ->where('type', FirmCreditTransaction::TYPE_ADMIN_ADJUSTMENT)
            ->select('firm_id', DB::raw('sum(amount) as amount'))
            ->groupBy('firm_id')
            ->get()
            ->keyBy('firm_id');

        // Manuel yükleme geliri (tahmini): pozitif yönetici eklemeleri × güncel birim fiyat
        $unitPrice = $this->firmCreditService->unitPrice();
        $manualRevenueEstimate = $adminAddedPositive * $unitPrice;
        $totalRevenue = $soldRevenue + $manualRevenueEstimate;

        // Firma bazlı birleştirme (satış + tüketim + manuel ekleme)
        $firmIds = $soldByFirm->keys()
            ->merge($consumedByFirm->keys())
            ->merge($adminAddedByFirm->keys())
            ->unique()->filter()->values();
        $firms = Firm::query()->whereIn('id', $firmIds)->get(['id', 'name', 'credit_balance'])->keyBy('id');

        $rows = [];
        foreach ($firmIds as $fid) {
            $firm = $firms->get($fid);
            $rows[] = [
                'firm_name' => $firm?->name ?? ('#'.$fid),
                'purchased_credits' => (int) ($soldByFirm[$fid]->credits ?? 0),
                'purchased_revenue' => (float) ($soldByFirm[$fid]->revenue ?? 0),
                'admin_added' => (int) ($adminAddedByFirm[$fid]->amount ?? 0),
                'consumed_credits' => (int) abs((int) ($consumedByFirm[$fid]->amount ?? 0)),
                'consumed_orders' => (int) ($consumedByFirm[$fid]->cnt ?? 0),
                'balance' => (int) ($firm?->credit_balance ?? 0),
            ];
        }
        usort($rows, fn ($a, $b) => ($b['purchased_revenue'] + $b['admin_added']) <=> ($a['purchased_revenue'] + $a['admin_added']));

        return [
            'sold_credits' => $soldCredits,
            'sold_revenue' => $soldRevenue,
            'sold_count' => $soldCount,
            'pending_count' => $pendingCount,
            'pending_credits' => $pendingCredits,
            'pending_revenue' => $pendingRevenue,
            'loaded' => $loaded,
            'consumed' => $consumed,
            'admin_added' => $adminAdded,
            'admin_added_positive' => $adminAddedPositive,
            'unit_price' => $unitPrice,
            'manual_revenue_estimate' => $manualRevenueEstimate,
            'total_revenue' => $totalRevenue,
            'rows' => $rows,
        ];
    }
}
