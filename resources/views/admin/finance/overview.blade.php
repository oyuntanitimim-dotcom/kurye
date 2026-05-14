@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-2">{{ $title }}</h1>
<p class="text-sm text-slate-600 mb-4">Tüm kurye şirketleri — teslim edilmiş siparişlere göre platform paket ücreti ve hareket özetleri.</p>

@include('admin.finance._subnav')

@include('firm.finance._date_filter', ['action' => route('admin.finance.index'), 'filters' => $filters])

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
        <p class="mt-2 text-xs text-amber-900/70">Tüm şirketlerdeki teslimlerden biriken platform payı.</p>
    </div>
    <div class="rounded-xl border border-emerald-100 bg-emerald-50/80 p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-emerald-900/80">İşletme paket ücreti</p>
        <p class="mt-1 text-2xl font-semibold text-emerald-950">{{ number_format($restaurantCommissionTotal, 2) }} ₺</p>
        <p class="mt-2 text-xs text-emerald-900/70">İşletmelerden kesilen teslim başı sabit ücret toplamı (kurye şirketi operasyonu).</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-slate-500">Kurye teslim ücreti (kayıtlı)</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($courierPayoutTotal, 2) }} ₺</p>
        <p class="mt-2 text-xs text-slate-500">Teslim başı ücret modelindeki kurye ödemeleri.</p>
    </div>
</div>

<div class="max-w-2xl rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
    <p class="font-medium text-slate-800 mb-2">Notlar</p>
    <p class="mb-3">Rakamlar sipariş verisinden üretilir; fatura veya e-belge kesimi bu sürümde yok.</p>
    <p class="mb-3">Şirket bazlı döküm için <a href="{{ route('admin.finance.firms', request()->only(['date_from', 'date_to'])) }}" class="font-medium text-amber-800 hover:underline">Kurye şirketleri</a> sayfasına geçin.</p>
    <p>Aylık günlük mutabakat ve CSV için <a href="{{ route('admin.finance.reconciliation') }}" class="font-medium text-amber-800 hover:underline">Mutabakat</a>.</p>
</div>
@endsection
