@php use App\Enums\OrderStatus; @endphp
@extends('shop.layout')

@section('content')
<h1 class="text-2xl font-semibold mb-2">Sipariş #{{ $order->id }}</h1>
<p class="text-slate-600 mb-6">{{ $order->restaurant?->name }} · {{ OrderStatus::tryFrom($order->status)?->label() ?? $order->status }}</p>
<ul class="space-y-2 text-sm mb-6">
    @foreach($order->items as $i)
        <li class="flex justify-between border-b border-slate-100 py-2">
            <span>{{ $i->product_name ?? $i->product?->name }} × {{ $i->quantity }}</span>
            <span>{{ number_format((float) $i->price * $i->quantity, 2) }} ₺</span>
        </li>
    @endforeach
</ul>
<div class="text-sm text-slate-600 space-y-1 mb-2">
    <div class="flex justify-between"><span>Teslimat</span><span class="tabular-nums">{{ number_format((float) $order->delivery_fee, 2) }} ₺</span></div>
</div>
<p class="font-semibold">Toplam: {{ number_format((float) $order->total_price, 2) }} ₺</p>

@if($order->status === OrderStatus::Delivered->value && $order->user_id !== null)
    @if($order->review)
        <div class="mt-8 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <h2 class="text-lg font-medium mb-2">Yorumunuz</h2>
            <p class="text-amber-600 font-medium">{{ str_repeat('★', (int) $order->review->rating) }}{{ str_repeat('☆', 5 - (int) $order->review->rating) }}</p>
            @if($order->review->comment)
                <p class="text-sm text-slate-700 mt-2 whitespace-pre-wrap">{{ $order->review->comment }}</p>
            @endif
        </div>
    @else
        <h2 class="text-lg font-medium mt-8 mb-3">Siparişi değerlendirin</h2>
        <form method="post" action="{{ route('shop.orders.review', $order) }}" class="max-w-lg space-y-3">
            @csrf
            <div>
                <label class="text-sm text-slate-600">Puan (1–5)</label>
                <select name="rating" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @for($s = 5; $s >= 1; $s--)
                        <option value="{{ $s }}">{{ $s }} yıldız</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="text-sm text-slate-600">Yorum (isteğe bağlı)</label>
                <textarea name="comment" rows="3" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">{{ old('comment') }}</textarea>
            </div>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">Gönder</button>
        </form>
    @endif
@endif

<h2 class="text-lg font-medium mt-8 mb-2">Durum geçmişi</h2>
<ul class="text-sm text-slate-600 space-y-1">
    @foreach($order->statusHistories as $h)
        <li>{{ $h->created_at->format('d.m.Y H:i') }} — {{ OrderStatus::tryFrom($h->status)?->label() ?? $h->status }}</li>
    @endforeach
</ul>
@endsection
