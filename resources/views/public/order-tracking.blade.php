@extends('layouts.app')

@section('body')
@php use App\Enums\OrderStatus; @endphp
<div class="min-h-screen flex flex-col items-center justify-start p-6">
    <div class="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-semibold text-slate-900 mb-1">{{ $title }}</h1>
        <p class="text-sm text-slate-500 mb-6">{{ $order->firm?->name ?? 'Sipariş' }}</p>

        @if($revoked)
            <p class="text-sm text-slate-600 mb-4">Bu takip bağlantısı sipariş tamamlandıktan veya iptal edildikten sonra sınırlı bilgi gösterir.</p>
        @endif

        <dl class="space-y-3 text-sm mb-6">
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">Durum</dt>
                <dd class="font-medium text-slate-900">{{ $statusLabel }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">Restoran</dt>
                <dd class="text-slate-800">{{ $order->restaurant?->name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">Telefon</dt>
                <dd class="text-slate-800">{{ $phoneMasked }}</dd>
            </div>
        </dl>

        <div>
            <h2 class="text-sm font-medium text-slate-800 mb-2">Zaman çizelgesi</h2>
            <ul class="space-y-2 border-l-2 border-slate-200 pl-4">
                @foreach($histories as $h)
                    <li class="text-sm text-slate-700">
                        <span class="text-slate-500">{{ $h->created_at?->format('d.m.Y H:i') }}</span>
                        — {{ OrderStatus::tryFrom($h->status)?->label() ?? $h->status }}
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="text-xs text-slate-400 mt-8">Adres ve tam ad bilgisi gizlilik nedeniyle bu sayfada gösterilmez.</p>
    </div>
</div>
@endsection
