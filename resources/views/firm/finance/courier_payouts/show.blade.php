@extends('layouts.firm')

@section('content')
<div class="mb-4">
    <a href="{{ route('firm.finance.courier_payouts.index') }}" class="text-sm text-slate-600 hover:underline">← Kurye ödemeleri</a>
</div>
<h1 class="text-2xl font-semibold mb-2">{{ $title }}</h1>
@if($s->status === 'voided')
    <p class="text-sm text-amber-800 mb-2">Bu kayıt iptal edildi; siparişler ve cari hareketler o an açıldı. Üstteki özet tutarlar bu kaydın o an aldığı anlık değerlerdir; alt tablolar iptalden sonra boş olabilir.</p>
@endif

@if(session('status'))
    <p class="mb-4 text-sm text-emerald-800">{{ session('status') }}</p>
@endif

<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 mb-6 text-sm">
    <div class="rounded-xl border border-slate-200 bg-white p-3">
        <p class="text-xs uppercase text-slate-500">Kurye</p>
        <p class="font-semibold text-slate-900">{{ $s->courier?->name ?? '—' }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-3">
        <p class="text-xs uppercase text-slate-500">Dönem</p>
        <p class="text-slate-800">{{ $s->period_start->format('Y-m-d') }} — {{ $s->period_end->format('Y-m-d') }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-3">
        <p class="text-xs uppercase text-slate-500">Kayıt yapan</p>
        <p class="text-slate-800">{{ $s->recordedBy?->name ?? '—' }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-3">
        <p class="text-xs uppercase text-slate-500">Ödeme yöntemi / ref.</p>
        <p class="text-slate-800">{{ $s->paymentMethodEnum()?->label() }} @if($s->payment_reference) — {{ $s->payment_reference }} @endif</p>
    </div>
</div>

<div class="grid gap-3 sm:grid-cols-3 mb-6 text-sm">
    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3">
        <p class="text-xs uppercase text-slate-500">Hakediş (sipariş)</p>
        <p class="text-xl font-bold text-slate-900">{{ number_format((float) $s->earnings_from_orders, 2) }} ₺ <span class="text-sm font-normal text-slate-500">({{ $s->orders_count }} sipariş)</span></p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3">
        <p class="text-xs uppercase text-slate-500">Cari: kesinti / prim</p>
        <p class="text-xl font-bold text-slate-900">−{{ number_format((float) $s->ledger_deductions, 2) }} / +{{ number_format((float) $s->ledger_credits, 2) }}</p>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-3">
        <p class="text-xs uppercase text-emerald-800">Net ödeme</p>
        <p class="text-xl font-bold text-emerald-950">{{ number_format((float) $s->net_paid, 2) }} ₺</p>
    </div>
</div>
@if($s->notes)
    <p class="text-sm text-slate-600 mb-4"><span class="font-medium">Not:</span> {{ $s->notes }}</p>
@endif

<div class="mb-4 overflow-x-auto rounded-xl border border-slate-200 bg-white text-sm">
    <h3 class="px-3 py-2 text-xs font-semibold uppercase text-slate-500 border-b">Dahil siparişler</h3>
    <table class="w-full min-w-[28rem]">
        <thead>
            <tr class="border-b border-slate-200 text-left text-slate-500">
                <th class="py-2 px-3">#</th>
                <th class="py-2 px-3">Teslim</th>
                <th class="py-2 px-3">Hakediş</th>
            </tr>
        </thead>
        <tbody>
            @forelse($s->orders as $o)
                <tr class="border-b border-slate-100">
                    <td class="py-2 px-3">#{{ $o->id }}</td>
                    <td class="py-2 px-3 text-slate-600">{{ $o->updated_at->format('Y-m-d H:i') }}</td>
                    <td class="py-2 px-3 font-medium">{{ number_format((float)($o->courier_payout_amount ?? 0), 2) }} ₺</td>
                </tr>
            @empty
                <tr><td colspan="3" class="py-4 px-3 text-slate-500">Siparişe bağlanmadı (yalnız cari kapatma).</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mb-6 overflow-x-auto rounded-xl border border-slate-200 bg-white text-sm">
    <h3 class="px-3 py-2 text-xs font-semibold uppercase text-slate-500 border-b">Cari hareketler (kapanan)</h3>
    <table class="w-full min-w-[28rem]">
        <thead>
            <tr class="border-b border-slate-200 text-left text-slate-500">
                <th class="py-2 px-3">Tarih</th>
                <th class="py-2 px-3">Tür</th>
                <th class="py-2 px-3">Tutar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledger as $le)
                <tr class="border-b border-slate-100">
                    <td class="py-2 px-3 text-slate-600">{{ $le->entry_date->format('Y-m-d') }}</td>
                    <td class="py-2 px-3">{{ $le->kindEnum()?->label() ?? $le->entry_kind }}</td>
                    <td class="py-2 px-3 font-medium">{{ number_format((float) $le->amount, 2) }} ₺</td>
                </tr>
            @empty
                <tr><td colspan="3" class="py-4 px-3 text-slate-500">Cari hareket yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($s->status !== 'voided')
    <form method="post" action="{{ route('firm.finance.courier_payouts.void', $s) }}" onsubmit="return confirm('İptal: sipariş hakedişleri tekrar ödenmemiş olur, cari kalemler açılır. Devam?');" class="inline">
        @csrf
        <button type="submit" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-800 hover:bg-red-100">
            Bu kaydı iptal et
        </button>
    </form>
@endif
@endsection
