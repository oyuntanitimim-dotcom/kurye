@extends('shop.layout')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Sepetim</h1>
@if(empty($items))
    <p class="text-slate-600">Sepetiniz boş.</p>
@else
    <form method="post" action="{{ route('shop.cart.update') }}">
        @csrf
        <div class="space-y-4">
            @foreach($items as $row)
                <div class="flex justify-between border-b border-slate-100 py-3">
                    <div>
                        <div class="font-medium">{{ $row['product']->name }}</div>
                        <div class="text-sm text-slate-500">{{ number_format($row['product']->effectiveUnitPrice(), 2) }} ₺ × {{ $row['qty'] }}</div>
                    </div>
                    <input type="number" name="qty[{{ $row['product']->id }}]" value="{{ $row['qty'] }}" min="0" class="w-20 rounded border border-slate-300 px-2 py-1 text-sm">
                </div>
            @endforeach
        </div>
        <div class="mt-6 space-y-2 text-sm">
            <div class="flex justify-between items-center">
                <span class="text-slate-600">Ara toplam</span>
                <span class="font-medium tabular-nums">{{ number_format($subtotal, 2) }} ₺</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-600">Teslimat</span>
                <span class="font-medium tabular-nums">{{ number_format($delivery_fee, 2) }} ₺</span>
            </div>
            <div class="flex justify-between items-center border-t border-slate-200 pt-2">
                <span class="font-semibold">Tahmini toplam</span>
                <span class="text-xl font-bold tabular-nums">{{ number_format($subtotal + $delivery_fee, 2) }} ₺</span>
            </div>
        </div>
        @if(!empty($shop_fixed_delivery))
            <p class="text-xs text-emerald-900 mt-2 rounded border border-emerald-200 bg-emerald-50/80 px-2 py-1.5">Bu işletme mağaza teslim ücretini sabit belirlemiş: <strong class="tabular-nums">{{ number_format((float) $shop_fixed_delivery_amount, 2) }} ₺</strong> (firma mesafe ayarından bağımsız).</p>
        @elseif(!empty($delivery_distance_mode))
            <p class="text-xs text-amber-800 mt-2">Mesafeli ücret: kesin tutar ödeme adımında seçeceğiniz adrese göre hesaplanır.</p>
        @endif
        <button type="submit" class="mt-4 rounded-lg border border-slate-300 px-4 py-2 text-sm">Sepeti güncelle</button>
    </form>
    <a href="{{ route('shop.checkout') }}" class="mt-6 inline-block rounded-lg bg-amber-500 px-6 py-3 font-medium text-white">Siparişi tamamla</a>
@endif
@endsection
