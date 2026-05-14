@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Restoranlar</h1>

<form method="get" action="{{ route('admin.restaurants.index') }}" class="mb-6 flex flex-wrap gap-3 items-end text-sm">
    <div>
        <label class="block text-slate-600 mb-1">Kurye şirketi</label>
        <select name="firm_id" class="rounded border border-slate-300 px-3 py-2 min-w-[12rem]">
            <option value="">Tümü</option>
            @foreach($firms as $f)
                <option value="{{ $f->id }}" @selected((string) ($filters['firm_id'] ?? '') === (string) $f->id)>{{ $f->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-slate-600 mb-1">Ara (ad / telefon / adres)</label>
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="rounded border border-slate-300 px-3 py-2 w-56" placeholder="…">
    </div>
    <div>
        <label class="block text-slate-600 mb-1">Durum</label>
        <select name="status" class="rounded border border-slate-300 px-3 py-2">
            <option value="">Tümü</option>
            <option value="active" @selected(($filters['status'] ?? '') === 'active')>active</option>
            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>inactive</option>
        </select>
    </div>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white">Filtrele</button>
    <a href="{{ route('admin.restaurants.index') }}" class="text-amber-700 hover:underline py-2">Sıfırla</a>
</form>

<table class="w-full text-sm">
    <thead><tr class="border-b text-left text-slate-500"><th class="py-2">Ad</th><th>Tür</th><th>Kurye şirketi</th><th>Paket ücreti (₺)</th><th>Durum</th><th></th></tr></thead>
    <tbody>
        @foreach($restaurants as $r)
            <tr class="border-b border-slate-100">
                <td class="py-2 font-medium">{{ $r->name }}</td>
                <td>{{ $r->business_type->label() }}</td>
                <td>{{ $r->firm?->name }}</td>
                <td>{{ $r->fee_per_delivery !== null ? number_format((float) $r->fee_per_delivery, 2).' ₺' : '—' }}</td>
                <td>{{ $r->status }}</td>
                <td><a class="text-amber-800 hover:underline" href="{{ route('admin.restaurants.edit', $r) }}">Düzenle</a></td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $restaurants->links() }}</div>
@endsection
