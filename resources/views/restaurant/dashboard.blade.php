@php use App\Enums\OrderStatus; @endphp
@extends('layouts.restaurant')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-semibold">Firma özeti</h1>
        <p class="mt-1 text-sm text-slate-600">Aktif siparişler: <strong>{{ $pendingOrders->count() }}</strong></p>
    </div>
    <a href="{{ route('restaurant.orders.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50">
        Tüm siparişlere git
    </a>
</div>

<h2 class="text-lg font-medium mb-3">Aktif siparişler</h2>

@php
    $badge = function (string $status): array {
        return match ($status) {
            'pending' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-800', 'ring' => 'ring-amber-200'],
            'accepted' => ['bg' => 'bg-sky-50', 'text' => 'text-sky-800', 'ring' => 'ring-sky-200'],
            'preparing' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-800', 'ring' => 'ring-indigo-200'],
            'ready' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-800', 'ring' => 'ring-emerald-200'],
            default => ['bg' => 'bg-slate-50', 'text' => 'text-slate-700', 'ring' => 'ring-slate-200'],
        };
    };
@endphp

@if($pendingOrders->isEmpty())
    <div class="rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500 shadow-sm">
        Bekleyen aktif sipariş yok.
    </div>
@else
    <div class="grid gap-4 lg:grid-cols-2">
        @foreach($pendingOrders as $o)
            @php
                $b = $badge((string) $o->status);
                $label = OrderStatus::tryFrom((string) $o->status)?->label() ?? (string) $o->status;
                $customerName = $o->customerDisplayName();
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow transition-shadow">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('restaurant.orders.show', $o) }}" data-open-modal data-modal-title="Sipariş #{{ $o->id }}" data-modal-subtitle="Sipariş detayları" class="font-semibold text-panel-accent hover:underline">
                            #{{ $o->id }}
                        </a>
                        <div class="mt-1 truncate text-sm text-slate-600">{{ $customerName }}</div>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <span class="rounded-full px-2 py-0.5 ring-1 {{ $b['bg'] }} {{ $b['text'] }} {{ $b['ring'] }}">{{ $label }}</span>
                            <span>•</span>
                            <span>{{ $o->created_at?->format('d.m.Y H:i') }}</span>
                            <span>•</span>
                            <span class="font-medium text-slate-700">{{ number_format((float) $o->total_price, 2) }} ₺</span>
                        </div>
                    </div>
                    <a href="{{ route('restaurant.orders.show', $o) }}" data-open-modal data-modal-title="Sipariş #{{ $o->id }}" data-modal-subtitle="Sipariş detayları" class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-50">
                        Detay
                    </a>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @if($o->status === 'pending')
                        <form method="post" action="{{ route('restaurant.orders.accept', $o) }}">
                            @csrf
                            <button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-medium text-white hover:bg-emerald-700">Onayla</button>
                        </form>
                    @endif
                    @if($o->status === 'accepted')
                        <form method="post" action="{{ route('restaurant.orders.preparing', $o) }}">
                            @csrf
                            <button class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-medium text-white hover:bg-slate-800">Hazırlanıyor</button>
                        </form>
                    @endif
                    @if($o->status === 'preparing')
                        <form method="post" action="{{ route('restaurant.orders.ready', $o) }}" class="inline">
                            @csrf
                            <button class="rounded-lg bg-amber-500 px-3 py-2 text-xs font-medium text-white hover:bg-amber-600">Hazır</button>
                        </form>
                    @endif
                    @if($o->status === 'ready' && !$o->courier_id && $o->restaurant_courier_requested_at === null)
                        <form method="post" action="{{ route('restaurant.orders.request_courier', $o) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-orange-600 px-3 py-2 text-xs font-medium text-white hover:bg-orange-700">Kurye çağır</button>
                        </form>
                    @endif
                    @if(in_array($o->status, ['pending', 'accepted', 'preparing'], true))
                        <form method="post" action="{{ route('restaurant.orders.cancel', $o) }}" onsubmit="return confirm('Sipariş iptal edilsin mi?');">
                            @csrf
                            <button type="submit" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-50">İptal</button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
