<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Modules\Firms\Models\Firm;
use App\Modules\Firms\Models\FirmCreditPurchase;
use App\Modules\Firms\Models\FirmCreditTransaction;
use App\Modules\Firms\Services\FirmCreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CreditController extends Controller
{
    public function __construct(private readonly FirmCreditService $credit) {}

    public function settings(): View
    {
        return view('admin.credits.settings', [
            'title' => 'Kontör ayarları',
            'unitPrice' => $this->credit->unitPrice(),
            'creditsPerOrder' => $this->credit->globalCreditsPerOrder(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'credit_unit_price' => ['required', 'numeric', 'min:0', 'max:100000'],
            'credits_per_order' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        PlatformSetting::set('credit_unit_price', number_format((float) $data['credit_unit_price'], 2, '.', ''));
        PlatformSetting::set('credits_per_order', (int) $data['credits_per_order']);

        return back()->with('status', 'Kontör ayarları güncellendi.');
    }

    public function firms(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));

        $firms = Firm::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'like', '%'.$q.'%'))
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.credits.firms', [
            'title' => 'Firma kontörleri',
            'firms' => $firms,
            'globalCreditsPerOrder' => $this->credit->globalCreditsPerOrder(),
            'filters' => ['q' => $q],
        ]);
    }

    public function firmHistory(Request $request, Firm $firm): View
    {
        $type = (string) $request->input('type', '');
        $validTypes = [
            FirmCreditTransaction::TYPE_PURCHASE,
            FirmCreditTransaction::TYPE_ORDER_DEDUCTION,
            FirmCreditTransaction::TYPE_ADMIN_ADJUSTMENT,
            FirmCreditTransaction::TYPE_REFUND,
        ];

        $transactions = $firm->creditTransactions()
            ->with(['creator:id,name', 'order:id'])
            ->when(in_array($type, $validTypes, true), fn ($q) => $q->where('type', $type))
            ->latest()
            ->paginate(30)
            ->appends($request->query());

        return view('admin.credits.firm_history', [
            'title' => $firm->name.' — Kontör hareketleri',
            'firm' => $firm,
            'transactions' => $transactions,
            'filters' => ['type' => $type],
            'creditsPerOrder' => $this->credit->creditsPerOrder($firm),
        ]);
    }

    public function adjust(Request $request, Firm $firm): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'not_in:0', 'min:-1000000', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:190'],
        ]);

        $this->credit->addCredits(
            $firm,
            (int) $data['amount'],
            FirmCreditTransaction::TYPE_ADMIN_ADJUSTMENT,
            $data['description'] ?? 'Yönetici düzeltmesi',
            (int) Auth::id(),
        );

        return back()->with('status', 'Kontör güncellendi: '.($data['amount'] > 0 ? '+' : '').$data['amount']);
    }

    public function updateFirm(Request $request, Firm $firm): RedirectResponse
    {
        $data = $request->validate([
            'credits_per_order_override' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $firm->credits_per_order_override = $data['credits_per_order_override'] ?? null;
        $firm->save();

        return back()->with('status', 'Firma sipariş başı kontör değeri güncellendi.');
    }

    public function purchases(Request $request): View
    {
        $status = (string) $request->input('status', '');

        $purchases = FirmCreditPurchase::query()
            ->with(['firm', 'requester'])
            ->when(in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true),
                fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        return view('admin.credits.purchases', [
            'title' => 'Kontör satın alma talepleri',
            'purchases' => $purchases,
            'filters' => ['status' => $status],
            'pendingCount' => FirmCreditPurchase::query()->where('status', FirmCreditPurchase::STATUS_PENDING)->count(),
        ]);
    }

    public function approvePurchase(FirmCreditPurchase $purchase): RedirectResponse
    {
        if ($purchase->status !== FirmCreditPurchase::STATUS_PENDING) {
            return back()->with('error', 'Bu talep zaten işlenmiş.');
        }

        $this->credit->addCredits(
            $purchase->firm,
            (int) $purchase->credits,
            FirmCreditTransaction::TYPE_PURCHASE,
            'Kontör satın alma #'.$purchase->id.' onayı ('.number_format((float) $purchase->total_price, 2).' ₺)',
            (int) Auth::id(),
        );

        $purchase->update([
            'status' => FirmCreditPurchase::STATUS_APPROVED,
            'approved_by' => (int) Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Talep onaylandı ve kontör yüklendi.');
    }

    public function rejectPurchase(FirmCreditPurchase $purchase): RedirectResponse
    {
        if ($purchase->status !== FirmCreditPurchase::STATUS_PENDING) {
            return back()->with('error', 'Bu talep zaten işlenmiş.');
        }

        $purchase->update([
            'status' => FirmCreditPurchase::STATUS_REJECTED,
            'approved_by' => (int) Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Talep reddedildi.');
    }
}
