@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-2">{{ $title }}</h1>
<x-finance-online-only-notice />
<p class="text-sm text-slate-600 mb-4">Tüm kurye şirketleri birlikte — seçilen takvim ayına göre teslim bazlı mutabakat. Günlük satırlar <code class="rounded bg-slate-100 px-1 text-xs">updated_at</code> (teslim anı) ile gruplanır.</p>

@include('admin.finance._subnav')

<form method="get" action="{{ route('admin.finance.reconciliation') }}" class="mb-6 flex flex-wrap gap-3 items-end text-sm">
    <div>
        <label class="block text-slate-600 mb-1">Ay</label>
        <input type="month" name="month" value="{{ $month }}" class="rounded border border-slate-300 px-3 py-2">
    </div>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white">Göster</button>
    <a href="{{ route('admin.finance.reconciliation', ['month' => $month, 'export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-800 hover:bg-slate-50">CSV indir</a>
</form>

<p class="text-sm text-slate-600 mb-4">Dönem: <strong>{{ $monthLabel }}</strong></p>

<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 mb-8">
    <div class="rounded-lg border border-slate-200 bg-white p-3 text-sm">
        <p class="text-slate-500 text-xs uppercase">Teslim</p>
        <p class="text-xl font-semibold">{{ $deliveredCount }}</p>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-3 text-sm">
        <p class="text-slate-500 text-xs uppercase">Ciro</p>
        <p class="text-xl font-semibold">{{ number_format($revenueTotal, 2) }} ₺</p>
    </div>
    <div class="rounded-lg border border-amber-100 bg-amber-50/70 p-3 text-sm">
        <p class="text-amber-900/70 text-xs uppercase">Platform</p>
        <p class="text-xl font-semibold text-amber-950">{{ number_format($platformFeesTotal, 2) }} ₺</p>
    </div>
    <div class="rounded-lg border border-emerald-100 bg-emerald-50/70 p-3 text-sm">
        <p class="text-emerald-900/70 text-xs uppercase">İşl. paket</p>
        <p class="text-xl font-semibold text-emerald-950">{{ number_format($restaurantCommissionTotal, 2) }} ₺</p>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-3 text-sm">
        <p class="text-slate-500 text-xs uppercase">Kurye ücret</p>
        <p class="text-xl font-semibold">{{ number_format($courierPayoutTotal, 2) }} ₺</p>
    </div>
</div>

<h2 class="text-lg font-medium mb-3">Günlük döküm</h2>
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white text-sm max-w-5xl">
    <table class="w-full">
        <thead>
            <tr class="border-b border-slate-200 text-left text-slate-500">
                <th class="py-2 px-3">Gün</th>
                <th class="py-2 px-3">Adet</th>
                <th class="py-2 px-3">Ciro</th>
                <th class="py-2 px-3">Platform</th>
                <th class="py-2 px-3">İşl. paket</th>
                <th class="py-2 px-3">Kurye</th>
            </tr>
        </thead>
        <tbody>
            @forelse($daily as $row)
                <tr class="border-b border-slate-100">
                    <td class="py-2 px-3">{{ $row->d }}</td>
                    <td class="py-2 px-3">{{ $row->c }}</td>
                    <td class="py-2 px-3">{{ number_format((float) $row->revenue, 2) }} ₺</td>
                    <td class="py-2 px-3">{{ number_format((float) $row->platform_fees, 2) }} ₺</td>
                    <td class="py-2 px-3">{{ number_format((float) $row->commission, 2) }} ₺</td>
                    <td class="py-2 px-3">{{ number_format((float) $row->courier_payout, 2) }} ₺</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 px-3 text-center text-slate-500">Bu ayda teslim kaydı yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
