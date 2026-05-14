<?php

declare(strict_types=1);

namespace App\Http\Controllers\Firm;

use App\Enums\CourierPayoutPaymentMethod;
use App\Http\Controllers\Controller;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Couriers\Models\CourierPayoutSettlement;
use App\Services\CourierPayoutService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class CourierPayoutController extends Controller
{
    public function __construct(
        private readonly CourierPayoutService $payoutService
    ) {
    }

    public function index(Request $request): View
    {
        $firmId = (int) Auth::user()->firm_id;

        $q = CourierPayoutSettlement::query()
            ->where('firm_id', $firmId)
            ->with(['courier:id,name', 'recordedBy:id,name'])
            ->orderByDesc('id');

        if ($request->filled('courier_id')) {
            $cid = (int) $request->input('courier_id');
            if ($cid > 0) {
                $q->where('courier_id', $cid);
            }
        }

        $settle = $q->paginate(20)->withQueryString();

        return view('firm.finance.courier_payouts.index', [
            'title' => 'Finans — Kurye ödemeleri',
            'settle' => $settle,
            'filters' => $request->only(['courier_id']),
            'couriers' => Courier::query()->where('firm_id', $firmId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        $firmId = (int) Auth::user()->firm_id;
        $courierId = (int) $request->input('courier_id', 0);
        $preset = $request->string('preset', 'week')->toString();
        if (! in_array($preset, ['all', 'today', 'week', 'month', 'custom'], true)) {
            $preset = 'week';
        }

        [$from, $to] = $this->resolvePeriod($preset, $request);
        $ordersAllTime = $preset === 'all';

        $includeAll = $request->boolean('include_all_ledger', false);

        $embed = $request->boolean('embed');
        $preview = null;
        if ($courierId > 0) {
            $preview = $this->payoutService->preview(
                $firmId,
                $courierId,
                $from,
                $to,
                $includeAll,
                $ordersAllTime
            );
        }

        return view('firm.finance.courier_payouts.create', [
            'title' => 'Finans — Kurye ödeme / kapat',
            'embed' => $embed,
            'couriers' => Courier::query()->where('firm_id', $firmId)->orderBy('name')->get(['id', 'name']),
            'courierId' => $courierId,
            'preset' => $preset,
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
            'includeAllLedger' => $includeAll,
            'ordersAllTime' => $ordersAllTime,
            'preview' => $preview,
            'paymentMethods' => CourierPayoutPaymentMethod::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $firmId = (int) Auth::user()->firm_id;
        $courierId = (int) $request->input('courier_id', 0);
        $preset = $request->string('preset', 'week')->toString();
        if (! in_array($preset, ['all', 'today', 'week', 'month', 'custom'], true)) {
            $preset = 'week';
        }
        $ordersAllTime = $preset === 'all';
        if ($courierId <= 0) {
            return redirect()
                ->route('firm.finance.courier_payouts.create', $this->createQuery($request))
                ->withErrors('Kurye seçiniz.');
        }

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $from = Carbon::parse($request->date('date_from'));
        $to = Carbon::parse($request->date('date_to'));
        $includeAll = $request->boolean('include_all_ledger', false);
        $method = CourierPayoutPaymentMethod::tryFrom($request->string('payment_method')->toString());
        if ($method === null) {
            return $this->redirectToCreateOnFailure($request, 'Geçerli bir ödeme yöntemi seçiniz.');
        }

        try {
            $this->payoutService->createSettlement(
                $firmId,
                $courierId,
                (int) Auth::id(),
                $from,
                $to,
                $preset,
                $method,
                $request->input('payment_reference'),
                $request->input('notes'),
                $includeAll,
                $ordersAllTime
            );
        } catch (Throwable $e) {
            return $this->redirectToCreateOnFailure($request, $e->getMessage());
        }

        if ($request->boolean('embed')) {
            $request->session()->flash('status', 'Kurye ödeme kaydı oluşturuldu.');

            return redirect()->route('firm.finance.courier_payouts.embed_done');
        }

        return redirect()
            ->route('firm.finance.courier_payouts.index')
            ->with('status', 'Kurye ödeme kaydı oluşturuldu.');
    }

    public function embedDone(): View
    {
        return view('firm.finance.courier_payouts.embed_close', [
            'to' => route('firm.finance.courier_payouts.index'),
        ]);
    }

    public function show(CourierPayoutSettlement $settlement): View
    {
        $this->assertFirm($settlement);
        $settlement->load(['courier:id,name,phone', 'recordedBy:id,name', 'orders' => function ($q): void {
            $q->orderBy('updated_at');
        }]);
        $settlement->loadCount('orders');

        $ledger = $settlement->ledgerEntries()
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        return view('firm.finance.courier_payouts.show', [
            'title' => 'Finans — Ödeme detayı #'.$settlement->id,
            's' => $settlement,
            'ledger' => $ledger,
        ]);
    }

    public function void(CourierPayoutSettlement $settlement): RedirectResponse
    {
        $this->assertFirm($settlement);
        if ($settlement->status === 'voided') {
            return redirect()
                ->route('firm.finance.courier_payouts.show', $settlement)
                ->with('status', 'Zaten iptal edilmiş.');
        }
        $this->payoutService->voidSettlement($settlement->fresh());

        return redirect()
            ->route('firm.finance.courier_payouts.show', $settlement)
            ->with('status', 'Ödeme kaydı iptal edildi; siparişler tekrar ödenmemiş sayıldı, cari kalemler açıldı.');
    }

    private function assertFirm(CourierPayoutSettlement $settlement): void
    {
        if ((int) $settlement->firm_id !== (int) Auth::user()->firm_id) {
            abort(403);
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(string $preset, Request $request): array
    {
        if ($preset === 'all') {
            return [
                Carbon::parse('2000-01-01')->startOfDay(),
                now()->endOfDay(),
            ];
        }
        if ($preset === 'custom' && $request->filled('date_from') && $request->filled('date_to')) {
            return [
                Carbon::parse($request->date('date_from'))->startOfDay(),
                Carbon::parse($request->date('date_to'))->endOfDay(),
            ];
        }
        if ($preset === 'today') {
            $d = now()->startOfDay();

            return [$d, now()->endOfDay()];
        }
        if ($preset === 'week') {
            return [now()->startOfWeek(), now()->endOfWeek()];
        }
        if ($preset === 'month') {
            return [now()->copy()->startOfMonth(), now()->copy()->endOfMonth()];
        }

        $from = $request->date('date_from') ?? now()->startOfWeek();
        $to = $request->date('date_to') ?? now()->endOfWeek();

        return [Carbon::parse($from), Carbon::parse($to)];
    }

    /**
     * @return array<string, mixed>
     */
    private function createQuery(Request $request): array
    {
        $q = $request->only(['courier_id', 'preset', 'date_from', 'date_to']);
        if ($request->boolean('include_all_ledger')) {
            $q['include_all_ledger'] = 1;
        }
        if ($request->boolean('embed')) {
            $q['embed'] = 1;
        }

        return $q;
    }

    private function redirectToCreateOnFailure(Request $request, string $message): RedirectResponse
    {
        return redirect()
            ->route('firm.finance.courier_payouts.create', $this->createQuery($request))
            ->withInput()
            ->withErrors($message);
    }
}
