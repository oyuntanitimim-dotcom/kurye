@extends('layouts.restaurant')

@section('content')
@php($embed = request()->boolean('embed'))

@if(!$embed)
    <p class="mb-4"><a href="{{ route('restaurant.orders.index') }}" class="text-sm text-amber-700 hover:underline">← Sipariş listesi</a></p>
@endif

@if(!$embed)
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">{{ $title }}</h1>
        </div>
    </div>
@endif

@if(!$embed && in_array($order->status, ['pending', 'accepted', 'preparing'], true))
    <form method="post" action="{{ route('restaurant.orders.cancel', $order) }}" class="mb-6" onsubmit="return confirm('Sipariş iptal edilsin mi?');">
        @csrf
        <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800 hover:bg-red-100">Siparişi iptal et</button>
    </form>
@endif

@if($embed)
    <div class="screen-only">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold">{{ $title }}</h1>
                <p class="mt-1 text-sm text-slate-600">Detay görünüm (ekranda)</p>
            </div>
            <button type="button" onclick="window.print()" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50">
                Yazdır
            </button>
        </div>
        @include('partials.order-detail', ['order' => $order, 'showFirm' => false])
    </div>

    <div class="print-only">
        @include('partials.order-receipt', ['order' => $order])
    </div>
@else
    @include('partials.order-detail', ['order' => $order, 'showFirm' => false])
@endif

@if($embed)
    @push('scripts')
    <style>
        body { background: #fff !important; }

        .print-only { display: none; }

        @media print {
            @page { size: 80mm auto; margin: 4mm; }
            body { background: #fff !important; }
            a { color: inherit !important; text-decoration: none !important; }
            dialog { display: none !important; }
            .no-print { display: none !important; }

            .screen-only { display: none !important; }
            .print-only { display: block !important; }

            .receipt {
                width: 72mm;
                max-width: 72mm;
                margin: 0;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                color: #0f172a;
            }
        }
    </style>
    @endpush
@endif
@endsection
