@extends('layouts.firm')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold">Kuponlar</h1>
    <a href="{{ route('firm.coupons.create') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Yeni kupon</a>
</div>
<table class="w-full text-sm rounded-xl border border-slate-200 bg-white">
    <thead><tr class="border-b text-left text-slate-500"><th class="py-2 px-4">Kod</th><th>İndirim</th><th>Kullanım</th><th>Bitiş</th><th></th></tr></thead>
    <tbody>
        @foreach($coupons as $c)
            <tr class="border-b border-slate-100">
                <td class="py-2 px-4 font-mono">{{ $c->code }}</td>
                <td>{{ $c->discount }} ₺</td>
                <td>{{ $c->used_count }} / {{ $c->usage_limit }}</td>
                <td>{{ $c->expire_date?->format('d.m.Y') }}</td>
                <td class="text-right space-x-2">
                    <a class="text-amber-700 hover:underline" href="{{ route('firm.coupons.edit', $c) }}">Düzenle</a>
                    <form action="{{ route('firm.coupons.destroy', $c) }}" method="post" class="inline" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
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
