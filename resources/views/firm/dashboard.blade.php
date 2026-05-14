@php use App\Enums\OrderStatus; @endphp
@extends('layouts.firm')

@section('content')
<h1 class="text-2xl font-semibold mb-8">Kurye şirketi paneli</h1>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5 mb-10">
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="text-sm text-slate-500">Restoran</div>
        <div class="text-3xl font-bold">{{ $restaurantCount }}</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="text-sm text-slate-500">Sipariş (toplam)</div>
        <div class="text-3xl font-bold">{{ $orderCount }}</div>
    </div>
    <a href="{{ route('firm.orders.index') }}" class="rounded-xl border border-slate-200 bg-white p-6 block hover:border-amber-300/80 transition">
        <div class="text-sm text-slate-500">Aktif sipariş</div>
        <div class="text-3xl font-bold text-slate-900">{{ $activeOrdersCount }}</div>
        <p class="text-xs text-amber-800 mt-2 font-medium">Teslim / iptal hariç →</p>
    </a>
    <a href="{{ route('firm.orders.index', ['awaiting_courier' => 1]) }}" class="rounded-xl border border-amber-200 bg-amber-50/40 p-6 block hover:bg-amber-50/80 transition">
        <div class="text-sm text-amber-900/80">Hazır, kuryesiz</div>
        <div class="text-3xl font-bold text-amber-950">{{ $readyAwaitingCourierCount }}</div>
        <p class="text-xs text-amber-900/80 mt-2 font-medium">Restoran «Kurye çağır» dedikten sonra →</p>
    </a>
    <a href="{{ route('firm.couriers.index') }}" class="rounded-xl border border-slate-200 bg-white p-6 block hover:border-amber-300/80 transition">
        <div class="text-sm text-slate-500">Aktif kurye</div>
        <div class="text-3xl font-bold">{{ $activeCourierCount }}</div>
        <p class="text-xs text-slate-500 mt-2">Kurye listesi →</p>
    </a>
</div>

<div class="mb-10 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <x-finance-online-only-notice />
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h2 class="text-lg font-medium">Finans özeti ({{ $financeThisMonth['label'] }})</h2>
        <a href="{{ route('firm.finance.overview') }}" class="text-sm font-medium text-amber-700 hover:underline">Finans modülü →</a>
    </div>
    <p class="text-xs text-slate-500 mb-4">Bu ay teslim edilen siparişlere göre; detay ve tarih filtresi için Finans menüsünü kullanın.</p>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 text-sm">
        <div class="rounded-lg bg-slate-50 px-3 py-2">
            <span class="text-slate-500">Teslim</span>
            <p class="text-lg font-semibold tabular-nums">{{ $financeThisMonth['delivered'] }}</p>
        </div>
        <div class="rounded-lg bg-slate-50 px-3 py-2">
            <span class="text-slate-500">Ciro</span>
            <p class="text-lg font-semibold tabular-nums">{{ number_format($financeThisMonth['revenue'], 2) }} ₺</p>
        </div>
        <div class="rounded-lg bg-amber-50/80 px-3 py-2">
            <span class="text-amber-900/70">Platform</span>
            <p class="text-lg font-semibold tabular-nums text-amber-950">{{ number_format($financeThisMonth['platform'], 2) }} ₺</p>
        </div>
        <div class="rounded-lg bg-emerald-50/80 px-3 py-2">
            <span class="text-emerald-900/70">İşl. paket</span>
            <p class="text-lg font-semibold tabular-nums text-emerald-950">{{ number_format($financeThisMonth['commission'], 2) }} ₺</p>
        </div>
        <div class="rounded-lg bg-slate-50 px-3 py-2">
            <span class="text-slate-500">Kurye ücret</span>
            <p class="text-lg font-semibold tabular-nums">{{ number_format($financeThisMonth['courier'], 2) }} ₺</p>
        </div>
    </div>
</div>

<div class="mb-3">
    <h2 class="text-lg font-medium">Son siparişler</h2>
    <p class="text-xs text-slate-500 mt-1">Beklemede / onay / hazırlık aşamasındaki siparişler burada listelenmez; restoran <strong class="font-medium text-slate-600">Hazır</strong> yaptıktan sonraki aşamalar (ve kapanan kayıtlar) görünür.</p>
</div>
<div class="overflow-x-auto text-sm rounded-xl border border-slate-200 bg-white">
    <table class="w-full">
        <thead><tr class="text-left text-slate-500 border-b"><th class="py-2 px-4">#</th><th class="py-2 pr-2">Restoran</th><th class="py-2 pr-2">Müşteri</th><th class="py-2 pr-2">Durum</th><th class="py-2 px-4 w-0 whitespace-nowrap">İşlem</th></tr></thead>
        <tbody>
            @forelse($recentOrders as $o)
                @php
                    $canFirmCancel = ! in_array($o->status, [OrderStatus::Delivered->value, OrderStatus::Cancelled->value], true);
                @endphp
                <tr class="border-b border-slate-100">
                    <td class="py-2 px-4"><a href="{{ route('firm.orders.show', $o) }}" class="text-amber-700 hover:underline">#{{ $o->id }}</a></td>
                    <td class="py-2 pr-2">{{ $o->restaurant?->name }}</td>
                    <td class="py-2 pr-2">{{ $o->customerDisplayName() }}</td>
                    <td class="py-2 pr-2">{{ OrderStatus::tryFrom($o->status)?->label() ?? $o->status }}</td>
                    <td class="py-2 px-4 align-top">
                        @if($canFirmCancel)
                            <form method="post" action="{{ route('firm.orders.cancel', $o) }}" class="inline" onsubmit="return confirm('Sipariş #{{ $o->id }} iptal edilsin mi?');">
                                @csrf
                                <button type="submit" class="rounded bg-red-600 px-2 py-1 text-xs font-medium text-white hover:bg-red-700">İptal</button>
                            </form>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-6 px-4 text-center text-slate-500">Hazırdan sonraki aşamada sipariş yok (veya henüz yok).</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
