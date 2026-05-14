@php use App\Enums\OrderStatus; @endphp
@extends('layouts.courier')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Aktif siparişler</h1>
<div class="space-y-4 max-w-xl">
    @forelse($activeOrders as $o)
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="font-medium">#{{ $o->id }} · {{ $o->restaurant?->name }}</div>
            <div class="text-sm text-slate-600 mt-1">{{ $o->customer?->name }} · {{ OrderStatus::tryFrom($o->status)?->label() ?? $o->status }}</div>
            <div class="mt-3 flex flex-wrap gap-2">
                @if($o->status === 'ready' && !$o->courier_id && $o->restaurant_courier_requested_at)
                    <form method="post" action="{{ route('courier.orders.accept', $o) }}">@csrf<button class="rounded bg-emerald-600 px-3 py-1.5 text-xs text-white">Üstlen</button></form>
                @endif
                @if($o->status === 'courier_assigned')
                    <form method="post" action="{{ route('courier.orders.accept_firm_assignment', $o) }}">@csrf<button type="submit" class="rounded bg-emerald-600 px-3 py-1.5 text-xs text-white">Kabul et</button></form>
                    <form method="post" action="{{ route('courier.orders.decline_firm_assignment', $o) }}" onsubmit="return confirm('Atamayı reddetsin mi? Firma yeniden atama yapacak.');">@csrf
                        <input type="hidden" name="reason" value="unavailable">
                        <button type="submit" class="rounded bg-rose-600 px-3 py-1.5 text-xs text-white">Reddet</button>
                    </form>
                    <form method="post" action="{{ route('courier.orders.decline_firm_assignment', $o) }}" onsubmit="return confirm('Kurye şirketine devir isteği gitsin mi? Sipariş yeniden atama kuyruğuna döner.');">@csrf
                        <input type="hidden" name="reason" value="transfer">
                        <button type="submit" class="rounded border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs text-amber-950">Devir et</button>
                    </form>
                @endif
                @if($o->status === 'courier_accepted')
                    <form method="post" action="{{ route('courier.orders.picked_up', $o) }}">@csrf<button class="rounded bg-slate-800 px-3 py-1.5 text-xs text-white">Aldım</button></form>
                @endif
                @if($o->status === 'picked_up')
                    <form method="post" action="{{ route('courier.orders.on_the_way', $o) }}">@csrf<button class="rounded bg-slate-800 px-3 py-1.5 text-xs text-white">Yolda</button></form>
                @endif
                @if($o->status === 'on_the_way')
                    <form method="post" action="{{ route('courier.orders.delivered', $o) }}">@csrf<button class="rounded bg-amber-500 px-3 py-1.5 text-xs text-white">Teslim</button></form>
                @endif
            </div>
        </div>
    @empty
        <p class="text-slate-500 py-8">Aktif sipariş yok.</p>
    @endforelse
</div>
@endsection
