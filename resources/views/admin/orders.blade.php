@php use App\Enums\OrderStatus; @endphp
@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Siparişler</h1>

<form method="get" action="{{ route('admin.orders.index') }}" class="mb-6 flex flex-wrap gap-3 items-end text-sm">
    @if(!empty($filters['active']))
        <input type="hidden" name="active" value="1">
    @endif
    @if(!empty($filters['awaiting_courier']))
        <input type="hidden" name="awaiting_courier" value="1">
    @endif
    <div>
        <label class="block text-slate-600 mb-1">Kurye şirketi</label>
        <select name="firm_id" class="rounded border border-slate-300 px-3 py-2 min-w-[12rem]">
            <option value="">Tümü</option>
            @foreach($firms as $f)
                <option value="{{ $f->id }}" @selected(($filters['firm_id'] ?? '') == $f->id)>{{ $f->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-slate-600 mb-1">Durum</label>
        <select name="status" class="rounded border border-slate-300 px-3 py-2 min-w-[10rem]">
            <option value="">Tümü</option>
            @foreach($statuses as $st)
                <option value="{{ $st->value }}" @selected(($filters['status'] ?? '') === $st->value)>{{ $st->label() }}</option>
            @endforeach
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
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white">Filtrele</button>
    <a href="{{ route('admin.orders.index') }}" class="text-amber-700 hover:underline py-2">Sıfırla</a>
</form>

<table class="w-full text-sm">
    <thead><tr class="border-b text-left text-slate-500"><th class="py-2">#</th><th>Kurye şirketi</th><th>Restoran</th><th>Müşteri</th><th>Ödeme</th><th>Durum</th><th>Tutar</th></tr></thead>
    <tbody>
        @foreach($orders as $o)
            <tr class="border-b border-slate-100">
                <td class="py-2"><a href="{{ route('admin.orders.show', $o) }}" class="text-amber-700 hover:underline font-medium">#{{ $o->id }}</a></td>
                <td>{{ $o->firm?->name }}</td>
                <td>{{ $o->restaurant?->name }}</td>
                <td>{{ $o->customerDisplayName() }}</td>
                <td class="text-slate-700">{{ $o->paymentMethodLabel() }}</td>
                <td>{{ OrderStatus::tryFrom($o->status)?->label() ?? $o->status }}</td>
                <td>{{ number_format((float) $o->total_price, 2) }} ₺</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
