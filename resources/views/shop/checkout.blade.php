@extends('shop.layout')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Sipariş onayı</h1>
<p class="text-sm text-slate-600 mb-4">
    Ara toplam <span class="font-medium tabular-nums">{{ number_format($subtotal, 2) }} ₺</span>
    + teslimat <span id="checkout-delivery-fee" class="font-medium tabular-nums">{{ number_format($delivery_fee, 2) }} ₺</span>
    = <span id="checkout-estimated-total" class="font-semibold tabular-nums">{{ number_format($estimated_total, 2) }} ₺</span>
</p>
@if(!empty($shop_fixed_delivery))
    <p class="text-xs text-emerald-900 mb-4 rounded border border-emerald-200 bg-emerald-50/80 px-3 py-2">Bu işletme için teslim ücreti sabit: <strong class="tabular-nums">{{ number_format((float) $shop_fixed_delivery_amount, 2) }} ₺</strong>. Adres değişse de tutar aynı kalır.</p>
@elseif(!empty($delivery_distance_mode))
    <p class="text-xs text-amber-800 mb-4">Teslimat ücreti seçtiğiniz adrese göre hesaplanır (restoran ve adres koordinatları gerekir).</p>
@endif
<form method="post" action="{{ route('shop.checkout.store') }}" class="max-w-lg space-y-4">
    @csrf
    <div>
        <label class="text-sm text-slate-600">Teslimat adresi</label>
        <select id="checkout-address" name="address_id" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
            @foreach($addresses as $a)
                <option value="{{ $a->id }}" data-fee="{{ number_format($address_fees[$a->id] ?? 0, 2, '.', '') }}">{{ $a->title }} — {{ \Illuminate\Support\Str::limit($a->address, 60) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-sm text-slate-600">Ödeme</label>
        <select name="payment_method" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
            <option value="cash_on_delivery">Kapıda nakit</option>
            <option value="card_on_delivery">Kapıda kart</option>
            <option value="online">Online ödeme</option>
        </select>
    </div>
    <div>
        <label class="text-sm text-slate-600">Not (isteğe bağlı)</label>
        <textarea name="notes" rows="3" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">{{ old('notes') }}</textarea>
    </div>
    <button type="submit" class="rounded-lg bg-slate-900 px-6 py-3 text-white">Onayla</button>
</form>
<div id="checkout-pricing" class="hidden" data-subtotal="{{ number_format($subtotal, 2, '.', '') }}" aria-hidden="true"></div>
<script>
(function () {
    const sel = document.getElementById('checkout-address');
    const feeEl = document.getElementById('checkout-delivery-fee');
    const totEl = document.getElementById('checkout-estimated-total');
    const subEl = document.getElementById('checkout-pricing');
    if (!sel || !feeEl || !totEl || !subEl) return;
    const subtotal = parseFloat(subEl.getAttribute('data-subtotal') || '0');
    function fmt(n) {
        return Number(n).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺';
    }
    function refresh() {
        const opt = sel.options[sel.selectedIndex];
        const fee = opt ? parseFloat(opt.getAttribute('data-fee') || '0') : 0;
        feeEl.textContent = fmt(fee);
        totEl.textContent = fmt(subtotal + fee);
    }
    sel.addEventListener('change', refresh);
})();
</script>
@endsection
