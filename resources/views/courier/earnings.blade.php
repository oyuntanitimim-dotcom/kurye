@extends('layouts.courier')

@section('content')
<h1 class="text-2xl font-semibold mb-4">Kazançlar</h1>
<x-finance-online-only-notice />

<form method="get" action="{{ route('courier.earnings') }}" class="mb-6 flex flex-wrap gap-3 items-end text-sm">
    <div>
        <label class="block text-slate-600 mb-1">Dönem</label>
        <select name="period" class="rounded border border-slate-300 px-3 py-2 min-w-[10rem]">
            <option value="today" @selected($period === 'today')>Bugün</option>
            <option value="week" @selected($period === 'week')>Bu hafta</option>
            <option value="month" @selected($period === 'month')>Bu ay</option>
            <option value="custom" @selected($period === 'custom')>Özel</option>
        </select>
    </div>
    <div>
        <label class="block text-slate-600 mb-1">Başlangıç</label>
        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-slate-600 mb-1">Bitiş</label>
        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="rounded border border-slate-300 px-3 py-2">
    </div>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white">Göster</button>
</form>

<p class="text-slate-600">{{ $periodLabel }} — teslim edilen <strong>{{ $count }}</strong> sipariş.</p>
<p class="text-slate-600 mt-2">Sipariş brüt toplamı (ciro): <strong class="text-xl">{{ number_format($total, 2) }} ₺</strong></p>
<p class="text-slate-600 mt-2 mb-8">Teslim başı ücret toplamı (sistemde kayıtlı): <strong class="text-xl">{{ number_format($courierPayoutTotal, 2) }} ₺</strong></p>

@if($dailyBreakdown->isNotEmpty())
    <h2 class="text-lg font-medium mb-3">Günlük kırılım (son 14 kayıt)</h2>
    <div class="overflow-x-auto text-sm rounded-xl border border-slate-200 bg-white max-w-lg">
        <table class="w-full">
            <thead><tr class="border-b text-left text-slate-500"><th class="py-2 px-3">Tarih</th><th class="py-2 px-3">Adet</th><th class="py-2 px-3">Ciro</th><th class="py-2 px-3">Ücret</th></tr></thead>
            <tbody>
                @foreach($dailyBreakdown as $row)
                    <tr class="border-b border-slate-100">
                        <td class="py-2 px-3">{{ $row->d }}</td>
                        <td class="py-2 px-3">{{ $row->c }}</td>
                        <td class="py-2 px-3">{{ number_format((float) $row->revenue, 2) }} ₺</td>
                        <td class="py-2 px-3">{{ number_format((float) $row->payout, 2) }} ₺</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
