@extends('layouts.admin')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold">Kuponlar</h1>
    <a href="{{ route('admin.coupons.create') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Yeni kupon</a>
</div>

<form method="get" action="{{ route('admin.coupons.index') }}" class="mb-6 flex flex-wrap gap-3 items-end text-sm">
    <div>
        <label class="block text-slate-600 mb-1">Kurye şirketi</label>
        <select name="firm_id" class="rounded border border-slate-300 px-3 py-2 min-w-[12rem]">
            <option value="">Tümü</option>
            @foreach($firms as $f)
                <option value="{{ $f->id }}" @selected(($filters['firm_id'] ?? '') == $f->id)>{{ $f->name }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white">Filtrele</button>
    <a href="{{ route('admin.coupons.index') }}" class="text-amber-700 hover:underline py-2">Sıfırla</a>
</form>

<table class="w-full text-sm">
    <thead><tr class="border-b text-left text-slate-500"><th class="py-2">Kod</th><th>Kurye şirketi</th><th>İndirim</th><th>Kullanım</th><th>Bitiş</th><th></th></tr></thead>
    <tbody>
        @foreach($coupons as $c)
            <tr class="border-b border-slate-100">
                <td class="py-2 font-mono">{{ $c->code }}</td>
                <td>{{ $c->firm?->name }}</td>
                <td>{{ $c->discount }} ₺</td>
                <td>{{ $c->used_count }} / {{ $c->usage_limit }}</td>
                <td>{{ $c->expire_date?->format('d.m.Y') }}</td>
                <td class="text-right space-x-2">
                    <a class="text-amber-700 hover:underline" href="{{ route('admin.coupons.edit', $c) }}">Düzenle</a>
                    <form action="{{ route('admin.coupons.destroy', $c) }}" method="post" class="inline" onsubmit="return confirm('Bu kuponu silmek istediğinize emin misiniz?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-700 hover:underline">Sil</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $coupons->links() }}</div>
@endsection
