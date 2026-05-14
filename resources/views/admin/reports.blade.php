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
<p class="text-xs text-slate-500 mb-4 -mt-2">Finans özetleri teslim anı (<code>updated_at</code>) üzerinden; durum dağılımı tüm siparişler içindir.</p>

<p class="text-slate-600 mb-2">Toplam sipariş: <strong>{{ $orderTotal }}</strong></p>
<p class="text-slate-600 mb-2">Teslim edilen siparişlerin ciro toplamı: <strong>{{ number_format($revenueDelivered, 2) }} ₺</strong></p>
<p class="text-slate-600 mb-2">Teslimat ücreti toplamı: <strong>{{ number_format($deliveryFeesDelivered, 2) }} ₺</strong></p>
<p class="text-slate-600 mb-2">İndirim toplamı: <strong>{{ number_format($discountsDelivered, 2) }} ₺</strong></p>
<p class="text-slate-600 mb-2">Platform paket ücreti toplamı: <strong>{{ number_format($platformFeesDelivered, 2) }} ₺</strong></p>
<p class="text-slate-600 mb-2">İşletme paket ücreti toplamı: <strong>{{ number_format($restaurantCommissionDelivered, 2) }} ₺</strong></p>
<p class="text-slate-600 mb-2">Kurye ödemesi toplamı (kayıtlı): <strong>{{ number_format($courierPayoutDelivered, 2) }} ₺</strong></p>
<p class="text-slate-600 mb-6">Net (ciro - platform - işletme paket - kurye): <strong>{{ number_format($revenueDelivered - $platformFeesDelivered - $restaurantCommissionDelivered - $courierPayoutDelivered, 2) }} ₺</strong></p>

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
                <th class="py-2 px-4">Platform</th>
                <th class="py-2 px-4">İşl. paket</th>
                <th class="py-2 px-4">Kurye</th>
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
                    <td class="px-4">{{ number_format((float) $row->platform_fees, 2) }} ₺</td>
                    <td class="px-4">{{ number_format((float) $row->restaurant_commission, 2) }} ₺</td>
                    <td class="px-4">{{ number_format((float) $row->courier_payout, 2) }} ₺</td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-6 px-4 text-slate-500">Henüz teslim edilmiş sipariş yok.</td></tr>
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
                <th class="py-2 px-4">Platform</th>
                <th class="py-2 px-4">İşl. paket</th>
                <th class="py-2 px-4">Kurye ödemesi</th>
                <th class="py-2 px-4">Net</th>
            </tr>
        </thead>
        <tbody>
            @forelse($courierBreakdown as $row)
                @php
                    $net = (float) $row->revenue
                        - (float) $row->platform_fees
                        - (float) $row->restaurant_commission
                        - (float) $row->courier_payout;
                @endphp
                <tr class="border-b border-slate-100">
                    <td class="py-2 px-4">{{ $row->courier?->name ?? '—' }}</td>
                    <td class="px-4">{{ $row->firm?->name ?? '—' }}</td>
                    <td class="px-4">{{ $row->delivered_count }}</td>
                    <td class="px-4">{{ number_format((float) $row->revenue, 2) }} ₺</td>
                    <td class="px-4">{{ number_format((float) $row->discounts, 2) }} ₺</td>
                    <td class="px-4">{{ number_format((float) $row->platform_fees, 2) }} ₺</td>
                    <td class="px-4">{{ number_format((float) $row->restaurant_commission, 2) }} ₺</td>
                    <td class="px-4 font-medium">{{ number_format((float) $row->courier_payout, 2) }} ₺</td>
                    <td class="px-4">{{ number_format($net, 2) }} ₺</td>
                </tr>
            @empty
                <tr><td colspan="9" class="py-6 px-4 text-slate-500">Henüz kurye kırılımı yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
