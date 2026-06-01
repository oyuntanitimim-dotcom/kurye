<?php

declare(strict_types=1);

namespace App\Http\Controllers\Firm;

use App\Http\Controllers\Controller;
use App\Modules\Firms\Models\Firm;
use App\Modules\Firms\Models\FirmCreditPurchase;
use App\Modules\Firms\Services\FirmCreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CreditController extends Controller
{
    public function __construct(private readonly FirmCreditService $credit) {}

    public function index(Request $request): View
    {
        $firm = Firm::query()->findOrFail((int) Auth::user()->firm_id);

        $transactions = $firm->creditTransactions()
            ->with('order')
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        $purchases = $firm->creditPurchases()
            ->latest()
            ->limit(10)
            ->get();

        return view('firm.credits.index', [
            'title' => 'Kontör',
            'firm' => $firm,
            'balance' => (int) $firm->credit_balance,
            'creditsPerOrder' => $this->credit->creditsPerOrder($firm),
            'unitPrice' => $this->credit->unitPrice(),
            'transactions' => $transactions,
            'purchases' => $purchases,
            'pendingPurchase' => $firm->creditPurchases()
                ->where('status', FirmCreditPurchase::STATUS_PENDING)
                ->latest()
                ->first(),
        ]);
    }

    public function purchase(Request $request): RedirectResponse
    {
        $firm = Firm::query()->findOrFail((int) Auth::user()->firm_id);

        $data = $request->validate([
            'credits' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        $unitPrice = $this->credit->unitPrice();
        $credits = (int) $data['credits'];
        $total = round($unitPrice * $credits, 2);

        FirmCreditPurchase::query()->create([
            'firm_id' => $firm->id,
            'credits' => $credits,
            'unit_price' => $unitPrice,
            'total_price' => $total,
            'status' => FirmCreditPurchase::STATUS_PENDING,
            'requested_by' => (int) Auth::id(),
        ]);

        return back()->with('status', 'Kontör satın alma talebiniz alındı ('.$credits.' kontör · '.number_format($total, 2).' ₺). Onaylandığında bakiyenize eklenecek.');
    }
}
