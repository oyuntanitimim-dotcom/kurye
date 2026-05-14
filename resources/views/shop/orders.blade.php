@extends('shop.layout')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Siparişlerim</h1>
<div class="space-y-3">
    @foreach($orders as $o)
        <a href="{{ route('shop.orders.show', $o) }}" class="block rounded-lg border border-slate-200 bg-white p-4 hover:border-amber-400">
            <div class="flex justify-between">
                <span class="font-medium">#{{ $o->id }}</span>
                <span class="text-sm text-slate-500">{{ $o->created_at->format('d.m.Y H:i') }}</span>
            </div>
            <div class="text-sm text-slate-600 mt-1">{{ $o->restaurant?->name }} — {{ $o->status }}</div>
            <div class="text-amber-800 font-semibold mt-2">{{ number_format((float) $o->total_price, 2) }} ₺</div>
        </a>
    @endforeach
</div>
<div class="mt-6">{{ $orders->links() }}</div>
@endsection
