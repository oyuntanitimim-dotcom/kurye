@extends('shop.layout')

@section('content')
<h1 class="text-2xl font-semibold mb-2">{{ $restaurant->name }}</h1>
<p class="text-slate-600 mb-8">{{ $restaurant->address }}</p>

@foreach($restaurant->categories as $cat)
    <h2 class="text-lg font-medium mt-6 mb-3">{{ $cat->name }}</h2>
    <div class="space-y-3">
        @foreach($restaurant->products->where('category_id', $cat->id) as $p)
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-slate-100 bg-white p-4">
                <div>
                    <div class="font-medium">{{ $p->name }}</div>
                    <div class="text-sm text-slate-500">{{ $p->description }}</div>
                    <div class="text-amber-800 font-semibold mt-1">
                        @if($p->discounted_price !== null && (float) $p->discounted_price < (float) $p->price)
                            <span class="mr-2 text-sm font-normal text-slate-400 line-through">{{ number_format((float) $p->price, 2) }} ₺</span>
                        @endif
                        {{ number_format($p->effectiveUnitPrice(), 2) }} ₺
                    </div>
                </div>
                <form method="post" action="{{ route('shop.cart.add') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $p->id }}">
                    <input type="number" name="quantity" value="1" min="1" class="w-16 rounded border border-slate-300 px-2 py-1 text-sm">
                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">Sepete</button>
                </form>
            </div>
        @endforeach
    </div>
@endforeach

@php $uncat = $restaurant->products->where('category_id', null); @endphp
@if($uncat->count())
    <h2 class="text-lg font-medium mt-6 mb-3">Diğer</h2>
    <div class="space-y-3">
        @foreach($uncat as $p)
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-slate-100 bg-white p-4">
                <div>
                    <div class="font-medium">{{ $p->name }}</div>
                    <div class="text-amber-800 font-semibold mt-1">
                        @if($p->discounted_price !== null && (float) $p->discounted_price < (float) $p->price)
                            <span class="mr-2 text-sm font-normal text-slate-400 line-through">{{ number_format((float) $p->price, 2) }} ₺</span>
                        @endif
                        {{ number_format($p->effectiveUnitPrice(), 2) }} ₺
                    </div>
                </div>
                <form method="post" action="{{ route('shop.cart.add') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $p->id }}">
                    <input type="number" name="quantity" value="1" min="1" class="w-16 rounded border border-slate-300 px-2 py-1 text-sm">
                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">Sepete</button>
                </form>
            </div>
        @endforeach
    </div>
@endif
@endsection
