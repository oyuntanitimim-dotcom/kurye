@php
    $o = $order;
    $created = $o->created_at?->format('d.m.Y H:i') ?? '';
    $status = \App\Enums\OrderStatus::tryFrom((string) $o->status)?->label() ?? (string) $o->status;
    $channel = $o->sourceEnum()?->label() ?? '—';
    $payment = $o->paymentMethodLabel();
    $customer = $o->customerDisplayName();
    $phone = $o->customer_phone ?: ($o->customer?->phone ?? '');
    $addr = $o->deliveryAddress?->address ?? null;
    $notes = $o->notes ?? null;

    $subTotal = 0.0;
    foreach ($o->items as $it) {
        $subTotal += (float) $it->price * (int) $it->quantity;
    }
@endphp

<div class="receipt text-[12px] leading-snug">
    <div class="no-print mb-3 flex items-center justify-between gap-2">
        <div class="text-sm font-semibold">Paket fişi</div>
        <button type="button" onclick="window.print()" class="rounded border border-slate-200 bg-white px-3 py-1 text-xs font-medium hover:bg-slate-50">Yazdır</button>
    </div>

    <div class="text-center">
        <div class="text-sm font-bold">{{ $o->restaurant?->name ?? '—' }}</div>
        <div class="mt-0.5 text-[11px] text-slate-600">{{ $created }}</div>
        <div class="mt-1 text-[13px] font-bold">Sipariş #{{ $o->id }}</div>
    </div>

    <div class="my-3 border-t border-dashed border-slate-300"></div>

    <div class="space-y-1">
        <div class="flex justify-between gap-3"><span class="text-slate-600">Kanal</span><span class="font-medium">{{ $channel }}</span></div>
        <div class="flex justify-between gap-3"><span class="text-slate-600">Durum</span><span class="font-medium">{{ $status }}</span></div>
        <div class="flex justify-between gap-3"><span class="text-slate-600">Ödeme</span><span class="font-medium">{{ $payment }}</span></div>
    </div>

    <div class="my-3 border-t border-dashed border-slate-300"></div>

    <div class="space-y-1">
        <div class="text-slate-600">Müşteri</div>
        <div class="font-medium">{{ $customer }}@if($phone) · {{ $phone }}@endif</div>
        @if($addr)
            <div class="mt-1 text-slate-600">Adres</div>
            <div class="whitespace-pre-line">{{ $addr }}</div>
        @endif
    </div>

    @if($notes)
        <div class="my-3 border-t border-dashed border-slate-300"></div>
        <div class="text-slate-600">Not</div>
        <div class="whitespace-pre-line">{{ $notes }}</div>
    @endif

    <div class="my-3 border-t border-dashed border-slate-300"></div>

    <table class="w-full">
        <thead>
            <tr class="text-[11px] text-slate-600">
                <th class="py-1 text-left font-medium">Ürün</th>
                <th class="py-1 text-right font-medium">Adet</th>
                <th class="py-1 text-right font-medium">Tutar</th>
            </tr>
        </thead>
        <tbody>
            @foreach($o->items as $it)
                @php($line = (float) $it->price * (int) $it->quantity)
                <tr class="border-t border-slate-100">
                    <td class="py-1 pr-2 align-top">
                        <div class="font-medium">{{ $it->product_name }}</div>
                        <div class="text-[11px] text-slate-600">{{ number_format((float) $it->price, 2) }} ₺</div>
                    </td>
                    <td class="py-1 text-right align-top">{{ (int) $it->quantity }}</td>
                    <td class="py-1 text-right align-top font-medium whitespace-nowrap">{{ number_format($line, 2) }} ₺</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="my-3 border-t border-dashed border-slate-300"></div>

    <div class="space-y-1">
        <div class="flex justify-between gap-3"><span class="text-slate-600">Ara toplam</span><span class="font-medium">{{ number_format($subTotal, 2) }} ₺</span></div>
        @if((float) $o->delivery_fee > 0)
            <div class="flex justify-between gap-3"><span class="text-slate-600">Teslimat</span><span class="font-medium">{{ number_format((float) $o->delivery_fee, 2) }} ₺</span></div>
        @endif
        @if((float) $o->discount_amount > 0)
            <div class="flex justify-between gap-3"><span class="text-slate-600">İndirim</span><span class="font-medium">-{{ number_format((float) $o->discount_amount, 2) }} ₺</span></div>
        @endif
        <div class="flex justify-between gap-3 text-[13px]"><span class="font-bold">Genel toplam</span><span class="font-bold">{{ number_format((float) $o->total_price, 2) }} ₺</span></div>
    </div>

    <div class="mt-4 text-center text-[11px] text-slate-600">
        Afiyet olsun.
    </div>
</div>

