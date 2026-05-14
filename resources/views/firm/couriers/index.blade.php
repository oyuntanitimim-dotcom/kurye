@php use App\Enums\CourierCompensationType; @endphp
@extends('layouts.firm')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold">Kuryeler</h1>
    <a href="{{ route('firm.couriers.create') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Yeni kurye</a>
</div>
<table class="w-full text-sm border-collapse rounded-xl border border-slate-200 bg-white">
    <thead>
        <tr class="border-b border-slate-200 text-left text-slate-500">
            <th class="py-2 px-4">Ad</th>
            <th>E-posta</th>
            <th>Telefon</th>
            <th>Araç</th>
            <th>Ücret modeli</th>
            <th>Durum</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($couriers as $c)
            <tr class="border-b border-slate-100">
                <td class="py-2 px-4 font-medium">{{ $c->name }}</td>
                <td>{{ $c->user?->email ?? '—' }}</td>
                <td>{{ $c->phone ?? '—' }}</td>
                <td>{{ $c->vehicle_type ?? '—' }}</td>
                <td class="text-xs">
                    @php $ct = CourierCompensationType::tryFrom((string) $c->compensation_type); @endphp
                    {{ $ct?->label() ?? $c->compensation_type }}
                    @if($c->compensation_type === 'per_delivery' && $c->compensation_per_delivery !== null)
                        <span class="text-slate-500">({{ number_format((float) $c->compensation_per_delivery, 2) }} ₺/teslim)</span>
                    @elseif($c->compensation_type === 'monthly_salary' && $c->compensation_monthly_salary !== null)
                        <span class="text-slate-500">({{ number_format((float) $c->compensation_monthly_salary, 0) }} ₺/ay)</span>
                    @elseif($c->compensation_type === 'per_kilometer' && $c->compensation_per_km !== null)
                        <span class="text-slate-500">({{ number_format((float) $c->compensation_per_km, 2) }} ₺/km)</span>
                    @endif
                </td>
                <td>{{ $c->status }}</td>
                <td><a class="text-amber-700 hover:underline" href="{{ route('firm.couriers.edit', $c) }}">Düzenle</a></td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $couriers->links() }}</div>
@endsection
