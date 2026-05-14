@extends('layouts.firm')

@section('content')
<h1 class="text-2xl font-semibold mb-2">{{ $title }}</h1>
<p class="text-sm text-slate-600 mb-6">İşletme bazında teslim siparişleri ve paket ücreti tutarları. Platform borcunuz için alttaki özet satırına bakın.</p>

@include('firm.finance._date_filter', ['action' => route('firm.finance.balances'), 'filters' => $filters, 'restaurants' => $restaurants ?? null])

<div class="mb-6 grid gap-3 sm:grid-cols-2 max-w-2xl">
    <div class="rounded-lg border border-amber-200 bg-amber-50/60 px-4 py-3 text-sm">
        <span class="text-amber-900/80">Platform paket ücreti (toplam)</span>
        <p class="text-lg font-semibold text-amber-950">{{ number_format($platformFeesTotal, 2) }} ₺</p>
    </div>
    <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 px-4 py-3 text-sm">
        <span class="text-emerald-900/80">İşletme paket ücreti (toplam)</span>
        <p class="text-lg font-semibold text-emerald-950">{{ number_format($restaurantCommissionTotal, 2) }} ₺</p>
    </div>
</div>

<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white text-sm">
    <table class="w-full min-w-[32rem]">
        <thead>
            <tr class="border-b border-slate-200 text-left text-slate-500">
                <th class="py-3 px-4">İşletme</th>
                <th class="py-3 px-4">Teslim adet</th>
                <th class="py-3 px-4">Paket ücreti toplamı</th>
                <th class="py-3 px-4">Sipariş cirosu</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr class="border-b border-slate-100">
                    <td class="py-2 px-4 font-medium">{{ $row->restaurant?->name ?? '—' }}</td>
                    <td class="py-2 px-4">{{ $row->order_count }}</td>
                    <td class="py-2 px-4">{{ number_format((float) $row->commission_total, 2) }} ₺</td>
                    <td class="py-2 px-4">{{ number_format((float) $row->revenue_total, 2) }} ₺</td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-8 px-4 text-center text-slate-500">Bu aralıkta teslim kaydı yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
