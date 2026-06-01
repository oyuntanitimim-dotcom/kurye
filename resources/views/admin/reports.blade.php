@php use App\Enums\OrderStatus; @endphp
@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Raporlar</h1>
<x-finance-online-only-notice />

<form method="get" action="{{ route('admin.reports.index') }}" class="mb-6 flex flex-wrap gap-3 items-end text-sm">
    <div>
        <label class="block text-slate-600 mb-1">Teslim başlangıç</label>
        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-slate-600 mb-1">Teslim bitiş</label>
        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="rounded border border-slate-300 px-3 py-2">
    </div>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white">Uygula</button>
    <a href="{{ route('admin.reports.index') }}" class="text-amber-700 hover:underline py-2">Sıfırla</a>
</form>
<p class="text-xs text-slate-500 mb-6 -mt-2">Kontör satışları onay tarihine, hareketler işlem tarihine, sipariş finansı teslim anına göredir.</p>

<h2 class="text-lg font-medium mb-3">Kontör geliri (admin)</h2>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
        <div class="text-sm text-emerald-900/70">Toplam kontör geliri</div>
        <div class="text-3xl font-bold tabular-nums text-emerald-950">{{ number_format($credit['total_revenue'], 2) }} ₺</div>
        <p class="mt-1 text-xs text-emerald-900/70">Satış {{ number_format($credit['sold_revenue'], 2) }} ₺ + manuel {{ number_format($credit['manual_revenue_estimate'], 2) }} ₺</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="text-sm text-slate-500">Satılan kontör (onaylı)</div>
        <div class="text-3xl font-bold tabular-nums">{{ number_format($credit['sold_credits']) }}</div>
        <p class="mt-1 text-xs text-slate-500">{{ $credit['sold_count'] }} onaylı talep</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="text-sm text-slate-500">Manuel yüklenen kontör</div>
        <div class="text-3xl font-bold tabular-nums">{{ number_format($credit['admin_added_positive']) }}</div>
        <p class="mt-1 text-xs text-slate-500">yönetici eklemesi · birim {{ number_format($credit['unit_price'], 2) }} ₺</p>
    </div>
    <div class="rounded-xl border p-5 {{ $credit['pending_count'] > 0 ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white' }}">
        <div class="text-sm text-slate-500">Onay bekleyen</div>
        <div class="text-3xl font-bold tabular-nums {{ $credit['pending_count'] > 0 ? 'text-amber-700' : '' }}">{{ $credit['pending_count'] }}</div>
        <p class="mt-1 text-xs text-slate-500">{{ number_format($credit['pending_credits']) }} kontör · {{ number_format($credit['pending_revenue'], 2) }} ₺
            @if($credit['pending_count'] > 0)<a href="{{ route('admin.credits.purchases', ['status' => 'pending']) }}" class="ml-1 font-medium text-amber-700 hover:underline">→</a>@endif
        </p>
    </div>
</div>

<div class="mb-8 grid gap-3 sm:grid-cols-3 text-sm">
    <div class="rounded-lg bg-slate-50 px-4 py-3">
        <span class="text-slate-500">Yüklenen kontör (toplam)</span>
        <p class="text-lg font-semibold tabular-nums">{{ number_format($credit['loaded']) }}</p>
        <span class="text-xs text-slate-400">satış onayı + manuel</span>
    </div>
    <div class="rounded-lg bg-slate-50 px-4 py-3">
        <span class="text-slate-500">Yönetici düzeltmesi (net)</span>
        <p class="text-lg font-semibold tabular-nums">{{ $credit['admin_added'] > 0 ? '+' : '' }}{{ number_format($credit['admin_added']) }}</p>
        <span class="text-xs text-slate-400">manuel yükleme − düşme</span>
    </div>
    <div class="rounded-lg bg-slate-50 px-4 py-3">
        <span class="text-slate-500">Tüketilen kontör</span>
        <p class="text-lg font-semibold tabular-nums">{{ number_format($credit['consumed']) }}</p>
        <span class="text-xs text-slate-400">kurye atamalarında</span>
    </div>
</div>

<h2 class="text-lg font-medium mb-3">Firma bazlı kontör</h2>
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white text-sm shadow-sm mb-8">
    <table class="w-full min-w-[52rem]">
        <thead>
            <tr class="border-b text-left text-slate-500">
                <th class="py-2 px-4">Kurye şirketi</th>
                <th class="py-2 px-4">Satın alınan</th>
                <th class="py-2 px-4">Ödenen (₺)</th>
                <th class="py-2 px-4">Manuel yükleme</th>
                <th class="py-2 px-4">Tüketilen</th>
                <th class="py-2 px-4">Düşülen sipariş</th>
                <th class="py-2 px-4">Mevcut bakiye</th>
            </tr>
        </thead>
        <tbody>
            @forelse($credit['rows'] as $row)
                <tr class="border-b border-slate-100">
                    <td class="py-2 px-4 font-medium">{{ $row['firm_name'] }}</td>
                    <td class="px-4 tabular-nums">{{ number_format($row['purchased_credits']) }}</td>
                    <td class="px-4 tabular-nums">{{ number_format($row['purchased_revenue'], 2) }} ₺</td>
                    <td class="px-4 tabular-nums {{ $row['admin_added'] < 0 ? 'text-red-600' : '' }}">{{ $row['admin_added'] > 0 ? '+' : '' }}{{ number_format($row['admin_added']) }}</td>
                    <td class="px-4 tabular-nums">{{ number_format($row['consumed_credits']) }}</td>
                    <td class="px-4 tabular-nums">{{ number_format($row['consumed_orders']) }}</td>
                    <td class="px-4 tabular-nums font-medium {{ $row['balance'] <= 0 ? 'text-red-600' : '' }}">{{ number_format($row['balance']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-6 px-4 text-slate-500">Bu aralıkta kontör hareketi yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="text-lg font-medium mb-3">Sipariş özeti</h2>
<x-finance-online-only-notice />
<p class="text-slate-600 mb-2">Toplam sipariş: <strong>{{ $orderTotal }}</strong></p>
<p class="text-slate-600 mb-2">Teslim edilen siparişlerin ciro toplamı: <strong>{{ number_format($revenueDelivered, 2) }} ₺</strong></p>
<p class="text-slate-600 mb-2">Teslimat ücreti toplamı: <strong>{{ number_format($deliveryFeesDelivered, 2) }} ₺</strong></p>
<p class="text-slate-600 mb-6">İndirim toplamı: <strong>{{ number_format($discountsDelivered, 2) }} ₺</strong></p>

<h2 class="text-lg font-medium mb-3">Duruma göre adet</h2>
<ul class="space-y-2 text-sm mb-8 max-w-md">
    @foreach($byStatus as $status => $count)
        <li class="flex justify-between border-b border-slate-100 py-1">
            <span>{{ OrderStatus::tryFrom($status)?->label() ?? $status }}</span>
            <span class="font-medium">{{ $count }}</span>
        </li>
    @endforeach
</ul>

<h2 class="text-lg font-medium mb-3">Kurye şirketi bazlı (teslim edilen)</h2>
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white text-sm shadow-sm mb-8">
    <table class="w-full min-w-[54rem]">
        <thead>
            <tr class="border-b text-left text-slate-500">
                <th class="py-2 px-4">Kurye şirketi</th>
                <th class="py-2 px-4">Teslim</th>
                <th class="py-2 px-4">Ciro</th>
                <th class="py-2 px-4">İndirim</th>
                <th class="py-2 px-4">Teslimat</th>
                <th class="py-2 px-4">Kurye ödemesi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($firmBreakdown as $row)
                <tr class="border-b border-slate-100">
                    <td class="py-2 px-4">{{ $row->firm?->name ?? '—' }}</td>
                    <td class="px-4">{{ $row->order_count }}</td>
                    <td class="px-4">{{ number_format((float) $row->revenue, 2) }} ₺</td>
                    <td class="px-4">{{ number_format((float) $row->discounts, 2) }} ₺</td>
                    <td class="px-4">{{ number_format((float) $row->delivery_fees, 2) }} ₺</td>
                    <td class="px-4">{{ number_format((float) $row->courier_payout, 2) }} ₺</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 px-4 text-slate-500">Henüz teslim edilmiş sipariş yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="text-lg font-medium mb-3">Kurye bazlı ödeme / kesinti (teslim edilen)</h2>
<p class="text-xs text-slate-500 mb-3">En çok ödeme alan ilk 50 kurye (teslim filtresine göre).</p>
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white text-sm shadow-sm">
    <table class="w-full min-w-[64rem]">
        <thead>
            <tr class="border-b text-left text-slate-500">
                <th class="py-2 px-4">Kurye</th>
                <th class="py-2 px-4">Şirket</th>
                <th class="py-2 px-4">Teslim</th>
                <th class="py-2 px-4">Ciro</th>
                <th class="py-2 px-4">İndirim</th>
                <th class="py-2 px-4">Kurye ödemesi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($courierBreakdown as $row)
                <tr class="border-b border-slate-100">
                    <td class="py-2 px-4">{{ $row->courier?->name ?? '—' }}</td>
                    <td class="px-4">{{ $row->firm?->name ?? '—' }}</td>
                    <td class="px-4">{{ $row->delivered_count }}</td>
                    <td class="px-4">{{ number_format((float) $row->revenue, 2) }} ₺</td>
                    <td class="px-4">{{ number_format((float) $row->discounts, 2) }} ₺</td>
                    <td class="px-4 font-medium">{{ number_format((float) $row->courier_payout, 2) }} ₺</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 px-4 text-slate-500">Henüz kurye kırılımı yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
