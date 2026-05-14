@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-2">{{ $title }}</h1>
<p class="text-sm text-slate-600 mb-4">Teslim edilen siparişlerde kurye şirketi bazında ciro ve kesinti özetleri.</p>

@include('admin.finance._subnav')

@include('firm.finance._date_filter', ['action' => route('admin.finance.firms'), 'filters' => $filters])

<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white text-sm shadow-sm">
    <table class="w-full min-w-[42rem]">
        <thead>
            <tr class="border-b border-slate-200 text-left text-slate-500">
                <th class="py-3 px-4">Kurye şirketi</th>
                <th class="py-3 px-4">Teslim</th>
                <th class="py-3 px-4">Ciro</th>
                <th class="py-3 px-4">Platform</th>
                <th class="py-3 px-4">İşl. paket</th>
                <th class="py-3 px-4">Kurye ücret</th>
            </tr>
        </thead>
        <tbody>
            @forelse($firmBreakdown as $row)
                <tr class="border-b border-slate-100">
                    <td class="py-3 px-4 font-medium text-slate-900">{{ $row->firm?->name ?? '—' }}</td>
                    <td class="py-3 px-4">{{ $row->order_count }}</td>
                    <td class="py-3 px-4">{{ number_format((float) $row->revenue, 2) }} ₺</td>
                    <td class="py-3 px-4 text-amber-900">{{ number_format((float) $row->platform_fees, 2) }} ₺</td>
                    <td class="py-3 px-4">{{ number_format((float) $row->restaurant_commission, 2) }} ₺</td>
                    <td class="py-3 px-4">{{ number_format((float) $row->courier_payout, 2) }} ₺</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-8 px-4 text-center text-slate-500">Filtreye uyan teslim kaydı yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
