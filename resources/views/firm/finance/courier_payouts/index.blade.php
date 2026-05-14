@extends('layouts.firm')

@php
    $modalPayoutCreateUrl = route('firm.finance.courier_payouts.create', array_filter([
        'embed' => 1,
        'courier_id' => !empty($filters['courier_id']) ? (int) $filters['courier_id'] : null,
    ]));
@endphp

@section('content')
<h1 class="text-2xl font-semibold mb-2">{{ $title }}</h1>
<p class="text-sm text-slate-600 mb-4">Dönem bazlı kurye hakediş kapatma (sipariş ücreti + avans / gider) kayıtları.</p>

<dialog id="payoutCreateDialog" class="z-[200] w-[min(100%-1rem,72rem)] max-w-6xl rounded-2xl border-0 bg-white p-0 shadow-2xl open:flex open:max-h-[min(100%-1rem,92vh)] open:max-w-[min(100%-1rem,72rem)] open:flex-col">
    <div class="flex flex-shrink-0 items-center justify-between border-b border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4">
        <h2 class="text-sm font-semibold text-slate-900">Yeni ödeme / kapat</h2>
        <form method="dialog">
            <button type="submit" class="rounded-md px-2 py-1 text-sm text-slate-600 hover:bg-slate-200 hover:text-slate-900">Kapat</button>
        </form>
    </div>
    <iframe id="payoutCreateFrame" class="min-h-0 w-full grow border-0 bg-white" style="min-height: 75vh" title="Kurye ödeme" loading="lazy" src=""></iframe>
</dialog>

<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <button type="button" id="openPayoutCreateModal"
            class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
        Yeni ödeme / kapat
    </button>
    <a href="{{ route('firm.finance.courier_payouts.create') }}"
       class="text-sm text-slate-500 underline decoration-slate-300 hover:text-slate-800">Yeni pencere / tam sayfa</a>
    <form method="get" action="{{ route('firm.finance.courier_payouts.index') }}" class="flex flex-wrap items-end gap-2 text-sm">
        <div>
            <label class="block text-slate-600 mb-1">Kurye</label>
            <select name="courier_id" class="rounded border border-slate-300 px-3 py-2 min-w-[10rem]">
                <option value="">Tümü</option>
                @foreach($couriers as $c)
                    <option value="{{ $c->id }}" @selected((string)($filters['courier_id'] ?? '') === (string)$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3 py-2">Filtrele</button>
    </form>
</div>

@if(session('status'))
    <p class="mb-4 text-sm text-emerald-800">{{ session('status') }}</p>
@endif

<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white text-sm">
    <table class="w-full min-w-[36rem]">
        <thead>
            <tr class="border-b border-slate-200 text-left text-slate-500">
                <th class="py-3 px-4">Tarih</th>
                <th class="py-3 px-4">Kurye</th>
                <th class="py-3 px-4">Dönem</th>
                <th class="py-3 px-4">Hakediş (sipariş)</th>
                <th class="py-3 px-4">Cari (±)</th>
                <th class="py-3 px-4">Net</th>
                <th class="py-3 px-4">Yöntem</th>
                <th class="py-3 px-4">Durum</th>
            </tr>
        </thead>
        <tbody>
            @forelse($settle as $s)
                <tr class="border-b border-slate-100">
                    <td class="py-2 px-4 text-slate-600">{{ $s->created_at->translatedFormat('d M Y, H:i') }}</td>
                    <td class="py-2 px-4 font-medium"><a class="text-slate-900 hover:underline" href="{{ route('firm.finance.courier_payouts.show', $s) }}">{{ $s->courier?->name ?? '—' }}</a></td>
                    <td class="py-2 px-4 text-slate-600">{{ $s->period_start->format('Y-m-d') }} — {{ $s->period_end->format('Y-m-d') }}</td>
                    <td class="py-2 px-4">{{ number_format((float) $s->earnings_from_orders, 2) }} ₺ <span class="text-slate-500">({{ $s->orders_count }} s.)</span></td>
                    <td class="py-2 px-4">-{{ number_format((float) $s->ledger_deductions, 2) }} / +{{ number_format((float) $s->ledger_credits, 2) }}</td>
                    <td class="py-2 px-4 font-semibold">{{ number_format((float) $s->net_paid, 2) }} ₺</td>
                    <td class="py-2 px-4">{{ $s->paymentMethodEnum()?->label() ?? $s->payment_method }}</td>
                    <td class="py-2 px-4">@if($s->status === 'voided')<span class="text-amber-800">İptal</span>@else<span class="text-emerald-800">Geçerli</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-8 px-4 text-center text-slate-500">Henüz kayıt yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $settle->links() }}</div>
@endsection

@push('styles')
<style>
#payoutCreateDialog[open] {
    position: fixed;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    width: min(100% - 1rem, 72rem);
    max-width: 72rem;
    max-height: min(100vh - 1rem, 92vh);
    height: fit-content;
    margin: auto;
    border: 1px solid #e2e8f0;
    padding: 0;
    overflow: hidden;
}
#payoutCreateDialog::backdrop {
    background: rgba(15, 23, 42, 0.5);
}
</style>
@endpush

@push('scripts')
<script>
(function () {
  const d = document.getElementById('payoutCreateDialog');
  const f = document.getElementById('payoutCreateFrame');
  const b = document.getElementById('openPayoutCreateModal');
  const url = @json($modalPayoutCreateUrl);
  if (!d || !f || !b) return;
  b.addEventListener('click', function () {
    f.setAttribute('src', url);
    d.showModal();
  });
  d.addEventListener('close', function () {
    f.removeAttribute('src');
  });
})();
</script>
@endpush
