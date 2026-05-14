@extends('layouts.firm')

@section('content')
<h1 class="text-2xl font-semibold mb-2">{{ $title }}</h1>
<p class="text-sm text-slate-600 mb-6">Teslim edilen siparişlere göre ciro, platform paket ücreti, işletme paket ücreti ve kurye ödeme özetleri.</p>

@include('firm.finance._date_filter', ['action' => route('firm.finance.overview'), 'filters' => $filters, 'restaurants' => $restaurants ?? null])

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-8">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-slate-500">Teslim sipariş</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $deliveredCount }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-slate-500">Brüt sipariş tutarı (ciro)</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($revenueTotal, 2) }} ₺</p>
    </div>
    <div class="rounded-xl border border-amber-100 bg-amber-50/80 p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-amber-900/80">Platform paket ücreti</p>
        <p class="mt-1 text-2xl font-semibold text-amber-950">{{ number_format($platformFeesTotal, 2) }} ₺</p>
        <p class="mt-2 text-xs text-amber-900/70">Sözleşmeye göre platforma ödenecek tahmini tutar.</p>
    </div>
    <div class="rounded-xl border border-emerald-100 bg-emerald-50/80 p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-emerald-900/80">İşletme paket ücreti</p>
        <p class="mt-1 text-2xl font-semibold text-emerald-950">{{ number_format($restaurantCommissionTotal, 2) }} ₺</p>
        <p class="mt-2 text-xs text-emerald-900/70">Teslim başına işletmeden kesilen sabit ücret toplamı.</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-slate-500">Kurye teslim ücreti (kayıtlı)</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($courierPayoutTotal, 2) }} ₺</p>
        <p class="mt-2 text-xs text-slate-500">“Teslim başı sabit ücret” modelindeki kuryeler için otomatik hesaplanan tutarlar.</p>
    </div>
</div>
@endsection
