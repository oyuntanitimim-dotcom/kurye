@extends('layouts.firm')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold">{{ $title ?? 'Restoranlar' }}</h1>
    <a href="{{ route('firm.restaurants.create') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Yeni restoran</a>
</div>
<table class="w-full text-sm border-collapse rounded-xl border border-slate-200 bg-white">
    <thead>
        <tr class="border-b border-slate-200 text-left text-slate-500">
            <th class="py-2 px-4">Ad</th>
            <th>Tür</th>
            <th>Telefon</th>
            <th>Paket ücreti (₺)</th>
            <th>Durum</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($restaurants as $r)
            <tr class="border-b border-slate-100">
                <td class="py-2 px-4 font-medium">{{ $r->name }}</td>
                <td>{{ $r->business_type->label() }}</td>
                <td>{{ $r->phone ?? '—' }}</td>
                <td>{{ $r->fee_per_delivery !== null ? number_format((float) $r->fee_per_delivery, 2).' ₺' : 'Varsayılan' }}</td>
                <td>{{ $r->status }}</td>
                <td><a class="text-amber-700 hover:underline" href="{{ route('firm.restaurants.edit', $r) }}">Düzenle</a></td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $restaurants->links() }}</div>
@endsection
