@php use App\Enums\OrderStatus; @endphp
@extends('layouts.firm')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Raporlar</h1>
<x-finance-online-only-notice />

<form method="get" action="{{ route('firm.reports.index') }}" class="mb-6 flex flex-wrap gap-3 items-end text-sm">
    <div>
        <label class="block text-slate-600 mb-1">Tarih aralığı</label>
        <select name="preset" class="rounded border border-slate-300 px-3 py-2 min-w-[12rem]" onchange="this.form.requestSubmit()">
            <option value="">Özel</option>
            <option value="today" @selected(($filters['preset'] ?? '') === 'today')>Bugün</option>
            <option value="7d" @selected(($filters['preset'] ?? '') === '7d')>Son 7 gün</option>
            <option value="30d" @selected(($filters['preset'] ?? '') === '30d')>Son 30 gün</option>
            <option value="this_month" @selected(($filters['preset'] ?? '') === 'this_month')>Bu ay</option>
            <option value="last_month" @selected(($filters['preset'] ?? '') === 'last_month')>Geçen ay</option>
        </select>
    </div>
    <x-panel.searchable-select
        name="restaurant_id"
        label="İşletme"
        placeholder="İşletme ara…"
        :options="$restaurants->map(fn($r) => ['value' => $r->id, 'label' => $r->name])->all()"
        :value="($filters['restaurant_id'] ?? '')"
    />
    <div>
        <label class="block text-slate-600 mb-1">Oluşturma başlangıç</label>
        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-slate-600 mb-1">Oluşturma bitiş</label>
        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="rounded border border-slate-300 px-3 py-2">
    </div>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white">Filtrele</button>
    <a href="{{ route('firm.reports.index') }}" class="text-amber-700 hover:underline py-2">Sıfırla</a>
</form>

@php
    $chips = [];
    if (!empty($filters['restaurant_id'])) {
        $r = $restaurants->firstWhere('id', (int) $filters['restaurant_id']);
        $chips[] = ['label' => 'İşletme: '.($r?->name ?? ('#'.$filters['restaurant_id'])), 'without' => ['restaurant_id']];
    }
    if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
        $chips[] = ['label' => 'Tarih: '.(($filters['date_from'] ?? '…').' → '.($filters['date_to'] ?? '…')), 'without' => ['date_from', 'date_to', 'preset']];
    } elseif (!empty($filters['preset'])) {
        $map = ['today' => 'Bugün', '7d' => 'Son 7 gün', '30d' => 'Son 30 gün', 'this_month' => 'Bu ay', 'last_month' => 'Geçen ay'];
        $chips[] = ['label' => 'Preset: '.($map[$filters['preset']] ?? $filters['preset']), 'without' => ['preset']];
    }
@endphp
@if(!empty($chips))
    <div class="mb-4 flex flex-wrap gap-2 text-xs">
        @foreach($chips as $chip)
            <a
                href="{{ route('firm.reports.index', array_diff_key(request()->query(), array_flip($chip['without']))) }}"
                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-slate-700 hover:bg-slate-50"
                title="Kaldır"
            >
                <span>{{ $chip['label'] }}</span>
                <span class="text-slate-400">×</span>
            </a>
        @endforeach
    </div>
@endif
<p class="text-xs text-slate-500 mb-4">Toplam ve durum dağılımı seçilen <strong>oluşturma</strong> tarihine göre. Teslim ciro, <strong>teslim</strong> anı (<code>updated_at</code>) aynı aralıkta olan siparişlerin toplamıdır.</p>

@php $fcb = $courierDeliveredByPayment ?? null; @endphp

<p class="text-slate-600 mb-2">Toplam sipariş (filtreli): <strong>{{ $orderTotal }}</strong></p>
<p class="text-slate-600 mb-2">Teslim edilen ciro (filtreli): <strong>{{ number_format($revenueDelivered, 2) }} ₺</strong></p>
<p class="text-slate-600 mb-2">Müşteri teslimat ücreti toplamı (teslim, filtreli): <strong>{{ number_format($deliveryFeesDelivered, 2) }} ₺</strong> <span class="text-slate-500 text-sm">— siparişte müşterinin ödediği teslimat satırı; kurye şirketi gideri değil.</span></p>
<p class="text-slate-600 mb-2">İndirim toplamı (teslim, filtreli): <strong>{{ number_format($discountsDelivered, 2) }} ₺</strong></p>
<p class="text-slate-600 mb-2">Platform paket ücreti (teslim, filtreli): <strong>{{ number_format($platformFeesDelivered, 2) }} ₺</strong></p>
<p class="text-slate-600 mb-2">İşletme paket ücreti toplamı (teslim, filtreli): <strong>{{ number_format($restaurantCommissionDelivered, 2) }} ₺</strong></p>
<p class="text-slate-600 mb-2">Kurye teslim ücreti toplamı (kayıtlı): <strong>{{ number_format($courierPayoutsDelivered, 2) }} ₺</strong></p>
<p class="text-slate-600 mb-6">Net (ciro - platform - işletme paket - kurye): <strong>{{ number_format($revenueDelivered - $platformFeesDelivered - $restaurantCommissionDelivered - $courierPayoutsDelivered, 2) }} ₺</strong></p>

@if($fcb)
    <h2 class="text-lg font-medium text-slate-900 mb-3">Kurye ile teslim — ciro (ödeme türü)</h2>
    <p class="text-xs text-slate-500 mb-4">Seçilen teslim tarihi ve işletme filtresiyle; kurye kartları yalnızca kurye atanmış teslimlerdir. Üstteki <strong>teslim ciro</strong>, kurye kartları ile <strong>dükkan teslim</strong> cirosunun toplamına eşittir (aynı finans filtresine tabidir).</p>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-5 mb-4">
        <div class="rounded-xl border border-sky-200 bg-sky-50/80 p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-sky-900/80">Online ödeme</p>
            <p class="mt-1 text-2xl font-semibold text-sky-950">{{ number_format((float) ($fcb->rev_online ?? 0), 2) }} ₺</p>
            <p class="mt-2 text-xs text-sky-900/70">{{ (int) ($fcb->cnt_online ?? 0) }} teslim</p>
        </div>
        <div class="rounded-xl border border-violet-200 bg-violet-50/80 p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-violet-900/80">Kapıda kart</p>
            <p class="mt-1 text-2xl font-semibold text-violet-950">{{ number_format((float) ($fcb->rev_card ?? 0), 2) }} ₺</p>
            <p class="mt-2 text-xs text-violet-900/70">{{ (int) ($fcb->cnt_card ?? 0) }} teslim</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-amber-900/80">Kapıda nakit</p>
            <p class="mt-1 text-2xl font-semibold text-amber-950">{{ number_format((float) ($fcb->rev_cash ?? 0), 2) }} ₺</p>
            <p class="mt-2 text-xs text-amber-900/70">{{ (int) ($fcb->cnt_cash ?? 0) }} teslim</p>
        </div>
        @if(((int) ($fcb->cnt_other ?? 0)) > 0)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
                <p class="text-xs font-medium uppercase text-slate-600">Diğer / tanımsız</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format((float) ($fcb->rev_other ?? 0), 2) }} ₺</p>
                <p class="mt-2 text-xs text-slate-500">{{ (int) ($fcb->cnt_other ?? 0) }} teslim</p>
            </div>
        @endif
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-emerald-900/80">Dükkan teslim (ciro)</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-950">{{ number_format((float) ($deliveredShopRevenue ?? 0), 2) }} ₺</p>
            <p class="mt-2 text-xs text-emerald-900/70">{{ (int) ($deliveredShopCount ?? 0) }} teslim <span class="text-emerald-800/80">(kurye yok)</span></p>
        </div>
    </div>
    @php
        $courierRevSumF = (float) ($fcb->rev_online ?? 0) + (float) ($fcb->rev_card ?? 0) + (float) ($fcb->rev_cash ?? 0) + (float) ($fcb->rev_other ?? 0);
        $shopRevF = (float) ($deliveredShopRevenue ?? 0);
        $breakdownTotalF = $courierRevSumF + $shopRevF;
        $teslimCiroF = (float) ($revenueDelivered ?? 0);
        $reconOkF = abs($breakdownTotalF - $teslimCiroF) < 0.05;
    @endphp
    <p class="mb-8 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
        <strong>Kontrol:</strong> kurye kartları toplamı {{ number_format($courierRevSumF, 2) }} ₺ + dükkan teslim {{ number_format($shopRevF, 2) }} ₺ = <strong>{{ number_format($breakdownTotalF, 2) }} ₺</strong>
        @if($reconOkF)
            — <span class="text-emerald-800 font-medium">üstteki teslim ciro ({{ number_format($teslimCiroF, 2) }} ₺) ile uyumlu.</span>
        @else
            — üstteki teslim ciro {{ number_format($teslimCiroF, 2) }} ₺; aradaki fark genelde finans filtresi veya veri tutarsızlığından kaynaklanır.
        @endif
    </p>
@endif

@if(isset($courierBreakdown) && $courierBreakdown->isNotEmpty())
    <h2 class="text-lg font-medium mb-3">Kurye bazlı ödeme / kesinti (teslim)</h2>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white text-sm shadow-sm mb-8">
        <table class="w-full min-w-[64rem]">
            <thead>
                <tr class="border-b text-left text-slate-500">
                    <th class="py-2 px-4">Kurye</th>
                    <th class="py-2 px-4">Teslim</th>
                    <th class="py-2 px-4">Ciro</th>
                    <th class="py-2 px-4">İndirim</th>
                    <th class="py-2 px-4" title="Müşterinin ödediği teslimat">Müşteri teslimatı</th>
                    <th class="py-2 px-4">Platform</th>
                    <th class="py-2 px-4">İşl. paket</th>
                    <th class="py-2 px-4">Kurye ödemesi</th>
                    <th class="py-2 px-4">Net</th>
                </tr>
            </thead>
            <tbody>
                @foreach($courierBreakdown as $row)
                    @php
                        $net = (float) $row->revenue
                            - (float) $row->platform_fees
                            - (float) $row->restaurant_commission
                            - (float) $row->courier_payout;
                    @endphp
                    <tr class="border-b border-slate-100">
                        <td class="py-2 px-4">{{ $row->courier?->name ?? '—' }}</td>
                        <td class="px-4">{{ $row->delivered_count }}</td>
                        <td class="px-4">{{ number_format((float) $row->revenue, 2) }} ₺</td>
                        <td class="px-4">{{ number_format((float) $row->discounts, 2) }} ₺</td>
                        <td class="px-4">{{ number_format((float) $row->delivery_fees, 2) }} ₺</td>
                        <td class="px-4">{{ number_format((float) $row->platform_fees, 2) }} ₺</td>
                        <td class="px-4">{{ number_format((float) $row->restaurant_commission, 2) }} ₺</td>
                        <td class="px-4 font-medium">{{ number_format((float) $row->courier_payout, 2) }} ₺</td>
                        <td class="px-4">{{ number_format($net, 2) }} ₺</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if(!empty($topRestaurantsByRevenue) && $topRestaurantsByRevenue->isNotEmpty())
    <h2 class="text-lg font-medium mb-3">İşletme bazlı satış (teslim kalemleri)</h2>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white text-sm shadow-sm mb-8">
        <table class="w-full min-w-[48rem]">
            <thead>
                <tr class="border-b text-left text-slate-500">
                    <th class="py-2 px-4">İşletme</th>
                    <th class="py-2 px-4 text-right">Adet</th>
                    <th class="py-2 px-4 text-right">Ciro</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topRestaurantsByRevenue as $row)
                    <tr class="border-b border-slate-100">
                        <td class="py-2 px-4 font-medium">{{ $row->restaurant_name }}</td>
                        <td class="py-2 px-4 text-right">{{ (int) $row->qty }}</td>
                        <td class="py-2 px-4 text-right">{{ number_format((float) $row->revenue, 2) }} ₺</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if(!empty($topCategoriesByRevenue) && $topCategoriesByRevenue->isNotEmpty())
    <h2 class="text-lg font-medium mb-3">Kategori bazlı satış (teslim kalemleri)</h2>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white text-sm shadow-sm mb-8">
        <table class="w-full min-w-[48rem]">
            <thead>
                <tr class="border-b text-left text-slate-500">
                    <th class="py-2 px-4">Kategori</th>
                    <th class="py-2 px-4 text-right">Adet</th>
                    <th class="py-2 px-4 text-right">Ciro</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topCategoriesByRevenue as $row)
                    <tr class="border-b border-slate-100">
                        <td class="py-2 px-4 font-medium">{{ $row->category_name }}</td>
                        <td class="py-2 px-4 text-right">{{ (int) $row->qty }}</td>
                        <td class="py-2 px-4 text-right">{{ number_format((float) $row->revenue, 2) }} ₺</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if(!empty($topProductsByRevenue) && $topProductsByRevenue->isNotEmpty())
    <h2 class="text-lg font-medium mb-3">Ürün bazlı satış (teslim kalemleri)</h2>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white text-sm shadow-sm mb-8">
        <table class="w-full min-w-[56rem]">
            <thead>
                <tr class="border-b text-left text-slate-500">
                    <th class="py-2 px-4">Ürün</th>
                    <th class="py-2 px-4 text-right">Adet</th>
                    <th class="py-2 px-4 text-right">Ciro</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topProductsByRevenue as $row)
                    <tr class="border-b border-slate-100">
                        <td class="py-2 px-4 font-medium">{{ $row->product_name }}</td>
                        <td class="py-2 px-4 text-right">{{ (int) $row->qty }}</td>
                        <td class="py-2 px-4 text-right">{{ number_format((float) $row->revenue, 2) }} ₺</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<h2 class="text-lg font-medium mb-3">Duruma göre</h2>
<ul class="space-y-2 text-sm mb-8 max-w-md">
    @foreach($byStatus as $status => $count)
        <li class="flex justify-between border-b border-slate-100 py-1">
            <span>{{ OrderStatus::tryFrom($status)?->label() ?? $status }}</span>
            <span class="font-medium">{{ $count }}</span>
        </li>
    @endforeach
</ul>

<h2 class="text-lg font-medium mb-3">Son siparişler</h2>
<div class="overflow-x-auto text-sm rounded-xl border border-slate-200 bg-white max-w-5xl">
    <form method="get" action="{{ route('firm.reports.index') }}" class="flex flex-wrap items-end justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50/50">
        @foreach(request()->except(['recent_per_page', 'recent_sort', 'recent_page']) as $k => $v)
            @if(is_array($v))
                @foreach($v as $vv)
                    <input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endif
        @endforeach
        <div class="flex flex-wrap items-end gap-3 text-xs">
            <div>
                <label class="block text-slate-600 mb-1">Sıralama</label>
                <select name="recent_sort" class="rounded border border-slate-300 px-2 py-1" onchange="this.form.requestSubmit()">
                    <option value="created_at_desc" @selected(($filters['recent_sort'] ?? '') === 'created_at_desc')>Yeni → Eski</option>
                    <option value="created_at_asc" @selected(($filters['recent_sort'] ?? '') === 'created_at_asc')>Eski → Yeni</option>
                    <option value="total_desc" @selected(($filters['recent_sort'] ?? '') === 'total_desc')>Tutar ↓</option>
                    <option value="total_asc" @selected(($filters['recent_sort'] ?? '') === 'total_asc')>Tutar ↑</option>
                </select>
            </div>
            <div>
                <label class="block text-slate-600 mb-1">Satır</label>
                <select name="recent_per_page" class="rounded border border-slate-300 px-2 py-1" onchange="this.form.requestSubmit()">
                    @foreach([15, 30, 50] as $n)
                        <option value="{{ $n }}" @selected((int) ($filters['recent_per_page'] ?? 15) === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="text-xs text-slate-500">Liste: {{ $recentOrders->total() }}</div>
    </form>
    <table class="w-full">
        <thead><tr class="border-b text-left text-slate-500"><th class="py-2 px-4">#</th><th>Restoran</th><th>Müşteri</th><th>Ödeme</th><th>Durum</th><th>Tutar</th></tr></thead>
        <tbody>
            @foreach($recentOrders as $o)
                <tr class="border-b border-slate-100">
                    <td class="py-2 px-4"><a href="{{ route('firm.orders.show', $o) }}" class="text-amber-700 hover:underline">#{{ $o->id }}</a></td>
                    <td>{{ $o->restaurant?->name }}</td>
                    <td>{{ $o->customerDisplayName() }}</td>
                    <td class="text-slate-700">{{ $o->paymentMethodLabel() }}</td>
                    <td>{{ OrderStatus::tryFrom($o->status)?->label() ?? $o->status }}</td>
                    <td>{{ number_format((float) $o->total_price, 2) }} ₺</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-3 max-w-5xl">{{ $recentOrders->links() }}</div>
@endsection
