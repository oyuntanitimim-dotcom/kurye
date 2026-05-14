<?php

declare(strict_types=1);

namespace App\Http\Controllers\Firm;

use App\Enums\CourierLedgerEntryKind;
use App\Enums\CourierLedgerEntryStatus;
use App\Http\Controllers\Controller;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Couriers\Models\CourierLedgerEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CourierLedgerEntryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $firmId = (int) Auth::user()->firm_id;
        $data = $request->validate([
            'courier_id' => 'required|integer|min:1',
            'entry_kind' => [
                'required',
                'string',
                Rule::in(array_map(static fn (CourierLedgerEntryKind $k) => $k->value, CourierLedgerEntryKind::cases())),
            ],
            'amount' => 'required|numeric|min:0.01',
            'entry_date' => 'required|date',
            'method' => 'nullable|string|max:32',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        if (! Courier::query()->where('firm_id', $firmId)->whereKey($data['courier_id'])->exists()) {
            return redirect()->back()->withErrors('Kurye bulunamadı.');
        }

        CourierLedgerEntry::query()->create([
            'firm_id' => $firmId,
            'courier_id' => (int) $data['courier_id'],
            'entry_kind' => $data['entry_kind'],
            'amount' => $data['amount'],
            'entry_date' => $request->date('entry_date'),
            'method' => $data['method'] ?? null,
            'reference' => $data['reference'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => CourierLedgerEntryStatus::Open->value,
        ]);

        $params = $request->only([
            'courier_id', 'preset', 'date_from', 'date_to', 'include_all_ledger', 'embed',
        ]);

        return redirect()
            ->route('firm.finance.courier_payouts.create', $params)
            ->with('status', 'Cari kalem eklendi.');
    }

    public function destroy(CourierLedgerEntry $entry): RedirectResponse
    {
        if ((int) $entry->firm_id !== (int) Auth::user()->firm_id) {
            abort(403);
        }
        if ((string) $entry->status !== CourierLedgerEntryStatus::Open->value) {
            return redirect()->back()->withErrors('Sadece açık kalemler silinebilir.');
        }
        $entry->delete();

        return redirect()->back()->with('status', 'Cari kalem silindi.');
    }
}
