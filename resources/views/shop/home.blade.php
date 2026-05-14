@extends('shop.layout')

@section('content')
<h1 class="text-2xl font-semibold mb-2">Hoş geldiniz</h1>
<p class="text-slate-600 mb-8">{{ $firm->district }} / {{ $firm->city }} bölgesinde restoranları keşfedin.</p>
<div class="grid gap-4 sm:grid-cols-2">
    @foreach($restaurants as $r)
        <a href="{{ route('shop.restaurant', $r) }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-amber-400 transition">
            <div class="font-medium">{{ $r->name }}</div>
            <div class="text-sm text-slate-500">{{ $r->address }}</div>
        </a>
    @endforeach
</div>
@endsection
