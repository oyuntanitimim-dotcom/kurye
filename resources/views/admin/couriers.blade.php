@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Kuryeler</h1>
<table class="w-full text-sm">
    <thead><tr class="border-b text-left text-slate-500"><th class="py-2">Ad</th><th>Kurye şirketi</th><th>Telefon</th><th>Durum</th></tr></thead>
    <tbody>
        @foreach($couriers as $c)
            <tr class="border-b border-slate-100">
                <td class="py-2">{{ $c->name }}</td>
                <td>{{ $c->firm?->name }}</td>
                <td>{{ $c->phone }}</td>
                <td>{{ $c->status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $couriers->links() }}</div>
@endsection
