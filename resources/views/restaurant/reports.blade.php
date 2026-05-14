@php use App\Enums\OrderStatus; @endphp
@extends('layouts.restaurant')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Raporlar</h1>
<x-finance-online-only-notice />

<form method="get" action="{{ route('restaurant.reports.index') }}" class="mb-6 space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-wrap items-end gap-3 text-sm">
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Tarih aralığı</label>
            <select name="preset" class="min-w-[12rem] rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-900 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent" onchange="this.form.requestSubmit()">
                <option value="">Özel</option>
                <option value="today" @selected(($filters['preset'] ?? '') === 'today')>Bugün</option>
                <option value="7d" @selected(($filters['preset'] ?? '') === '7d')>Son 7 gün</option>
                <option value="30d" @selected(($filters['preset'] ?? '') === '30d')>Son 30 gün</option>
                <option value="this_month" @selected(($filters['preset'] ?? '') === 'this_month')>Bu ay</option>
                <option value="last_month" @selected(($filters['preset'] ?? '') === 'last_month')>Geçen ay</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Oluşturma başlangıç</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-900 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Oluşturma bitiş</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-900 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
        </div>
        <button type="submit" class="rounded-lg bg-panel-accent px-4 py-2 text-sm font-medium text-white hover:opacity-95">Uygula</button>
        <a href="{{ route('restaurant.reports.index') }}" class="py-2 text-sm text-slate-600 hover:text-slate-900 hover:underline">Sıfırla</a>
    </div>
</form>

@php
    $chips = [];
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
                href="{{ route('restaurant.reports.index', array_diff_key(request()->query(), array_flip($chip['without']))) }}"
                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-slate-700 hover:bg-slate-50"
                title="Kaldır"
            >
                <span>{{ $chip['label'] }}</span>
                <span class="text-slate-400">×</span>
            </a>
        @endforeach
    </div>
@endif
<p class="text-xs text-slate-500 mb-4">Toplam ve durum dağılımı seçilen <strong>oluşturma</strong> tarihine göre. Teslim özetleri, teslim anına (<code>updated_at</code>) göre; ciro tüm ödeme türlerini (kapıda nakit / kart / online) içerir.</p>

@php
    $cbp = $courierDeliveredByPayment ?? null;
@endphp

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-8">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-slate-500">Toplam sipariş</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $orderTotal }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-slate-500">Teslim (adet)</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $deliveredCount ?? 0 }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-slate-500">İptal (adet)</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $cancelledCount ?? 0 }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-slate-500">Dükkan teslim (adet)</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $deliveredShopCount ?? 0 }}</p>
        <p class="mt-2 text-xs text-slate-500">Kurye yokken teslim edilen siparişler.</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-slate-500">Kurye ile teslim (adet)</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $deliveredCourierCountAllPayments ?? ($deliveredCourierCount ?? 0) }}</p>
        <p class="mt-2 text-xs text-slate-500">Tüm ödeme yöntemleri (nakit / kart / online). Ciro kartları aşağıda ayrı ayrı.</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-slate-500">Teslim ciro</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($revenueDelivered, 2) }} ₺</p>
        <p class="mt-2 text-xs text-slate-500">Müşteriden tahsil edilen sipariş toplamı (ürün + teslimat − indirim). Ödeme türü kırılımı aşağıdaki kartlarda.</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-slate-500">Teslimat ücreti (müşteri)</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($deliveryFeesDelivered, 2) }} ₺</p>
        <p class="mt-2 text-xs text-slate-500">Müşterinin siparişte ödediği teslimat bedelinin toplamı; işletme gideri değildir.</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-slate-500">İndirim</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($discountsDelivered, 2) }} ₺</p>
    </div>
    <div class="rounded-xl border border-emerald-100 bg-emerald-50/80 p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-emerald-900/80">Kurye şirketi paket ücreti (gider)</p>
        <p class="mt-1 text-2xl font-semibold text-emerald-950">{{ number_format((float) ($packageFeesDelivered ?? 0), 2) }} ₺</p>
        <p class="mt-2 text-xs text-emerald-900/70">Sadece <strong>kurye ile teslim</strong> edilen siparişlerden kesilir.</p>
    </div>
    <div class="rounded-xl border border-slate-900 bg-slate-900 p-4 shadow-sm">
        <p class="text-xs font-medium uppercase text-white/70">Net</p>
        <p class="mt-1 text-2xl font-semibold text-white">{{ number_format($revenueDelivered - (float) ($packageFeesDelivered ?? 0), 2) }} ₺</p>
        <p class="mt-2 text-xs text-white/60">Net = ciro - paket ücreti.</p>
    </div>
</div>

@if($cbp)
    <h2 class="text-lg font-medium text-slate-900 mb-3">Kurye ile teslim — ciro (ödeme türü)</h2>
    <p class="text-xs text-slate-500 mb-4">Aynı teslim filtreleme; kurye atanmış teslimler, ödeme türüne göre. Üst ciro, bu kırılımlar + dükkan teslimi ile uyumludur.</p>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-5 mb-4">
        <div class="rounded-xl border border-sky-200 bg-sky-50/80 p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-sky-900/80">Online ödeme</p>
            <p class="mt-1 text-2xl font-semibold text-sky-950">{{ number_format((float) ($cbp->rev_online ?? 0), 2) }} ₺</p>
            <p class="mt-2 text-xs text-sky-900/70">{{ (int) ($cbp->cnt_online ?? 0) }} teslim</p>
        </div>
        <div class="rounded-xl border border-violet-200 bg-violet-50/80 p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-violet-900/80">Kapıda kart</p>
            <p class="mt-1 text-2xl font-semibold text-violet-950">{{ number_format((float) ($cbp->rev_card ?? 0), 2) }} ₺</p>
            <p class="mt-2 text-xs text-violet-900/70">{{ (int) ($cbp->cnt_card ?? 0) }} teslim</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-amber-900/80">Kapıda nakit</p>
            <p class="mt-1 text-2xl font-semibold text-amber-950">{{ number_format((float) ($cbp->rev_cash ?? 0), 2) }} ₺</p>
            <p class="mt-2 text-xs text-amber-900/70">{{ (int) ($cbp->cnt_cash ?? 0) }} teslim</p>
        </div>
        @if(((int) ($cbp->cnt_other ?? 0)) > 0)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
                <p class="text-xs font-medium uppercase text-slate-600">Diğer / tanımsız</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format((float) ($cbp->rev_other ?? 0), 2) }} ₺</p>
                <p class="mt-2 text-xs text-slate-500">{{ (int) ($cbp->cnt_other ?? 0) }} teslim</p>
            </div>
        @endif
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-emerald-900/80">Dükkan teslim (ciro)</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-950">{{ number_format((float) ($deliveredShopRevenue ?? 0), 2) }} ₺</p>
            <p class="mt-2 text-xs text-emerald-900/70">{{ (int) ($deliveredShopCount ?? 0) }} teslim <span class="text-emerald-800/80">(kurye yok)</span></p>
        </div>
    </div>
    @php
        $courierRevSum = (float) ($cbp->rev_online ?? 0) + (float) ($cbp->rev_card ?? 0) + (float) ($cbp->rev_cash ?? 0) + (float) ($cbp->rev_other ?? 0);
        $shopRev = (float) ($deliveredShopRevenue ?? 0);
        $breakdownTotal = $courierRevSum + $shopRev;
        $teslimCiro = (float) ($revenueDelivered ?? 0);
        $reconOk = abs($breakdownTotal - $teslimCiro) < 0.05;
    @endphp
    <p class="mb-8 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
        <strong>Kontrol:</strong> kurye kartları toplamı {{ number_format($courierRevSum, 2) }} ₺ + dükkan teslim {{ number_format($shopRev, 2) }} ₺ = <strong>{{ number_format($breakdownTotal, 2) }} ₺</strong>
        @if($reconOk)
            — <span class="text-emerald-800 font-medium">üstteki teslim ciro ({{ number_format($teslimCiro, 2) }} ₺) ile uyumlu.</span>
        @else
            — üstteki teslim ciro {{ number_format($teslimCiro, 2) }} ₺; fark varsa tarih/ödeme türü yuvarlaması veya dükkan teslimi kontrol edin.
        @endif
    </p>
@endif

@if(isset($courierDelivered) && $courierDelivered->isNotEmpty())
    <details class="mb-8 rounded-xl border border-slate-200 bg-white shadow-sm">
        <summary class="cursor-pointer select-none px-4 py-3 text-lg font-medium">
            Kurye performansı (teslim)
            <span class="text-sm font-normal text-slate-500">— {{ $courierDelivered->count() }} kurye</span>
        </summary>
        <div class="overflow-x-auto text-sm border-t border-slate-100">
            <table class="w-full min-w-[52rem]">
                <thead>
                    <tr class="border-b text-left text-slate-500">
                        <th class="py-2 px-4">Kurye</th>
                        <th class="py-2 px-4 text-right">Teslim</th>
                        <th class="py-2 px-4 text-right">Ciro</th>
                        <th class="py-2 px-4 text-right" title="Müşterinin ödediği teslimat">Müşteri teslimatı</th>
                        <th class="py-2 px-4 text-right">İndirim</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($courierDelivered as $row)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 px-4 font-medium">{{ $row->courier?->name ?? ('#'.$row->courier_id) }}</td>
                            <td class="py-2 px-4 text-right">{{ (int) $row->delivered_count }}</td>
                            <td class="py-2 px-4 text-right">{{ number_format((float) $row->revenue, 2) }} ₺</td>
                            <td class="py-2 px-4 text-right">{{ number_format((float) $row->delivery_fees, 2) }} ₺</td>
                            <td class="py-2 px-4 text-right">{{ number_format((float) $row->discounts, 2) }} ₺</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
@endif

@if(isset($courierCancelled) && $courierCancelled->isNotEmpty())
    <details class="mb-8 rounded-xl border border-slate-200 bg-white shadow-sm max-w-3xl">
        <summary class="cursor-pointer select-none px-4 py-3 text-lg font-medium">
            Kurye performansı (iptal)
            <span class="text-sm font-normal text-slate-500">— {{ $courierCancelled->count() }} kurye</span>
        </summary>
        <div class="overflow-x-auto text-sm border-t border-slate-100">
            <table class="w-full">
                <thead>
                    <tr class="border-b text-left text-slate-500">
                        <th class="py-2 px-4">Kurye</th>
                        <th class="py-2 px-4 text-right">İptal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($courierCancelled as $row)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 px-4 font-medium">{{ $row->courier?->name ?? ('#'.$row->courier_id) }}</td>
                            <td class="py-2 px-4 text-right">{{ (int) $row->cancelled_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
@endif

<details class="rounded-xl border border-slate-200 bg-white text-sm shadow-sm max-w-2xl">
    <summary class="cursor-pointer select-none px-4 py-3 text-lg font-medium">
        Duruma göre
        <span class="text-sm font-normal text-slate-500">— {{ $byStatus->count() }} durum</span>
    </summary>
    <div class="overflow-x-auto border-t border-slate-100">
        <table class="w-full">
            <thead>
                <tr class="border-b border-slate-100 text-left text-slate-500">
                    <th class="py-3 px-4">Durum</th>
                    <th class="py-3 px-4 text-right">Adet</th>
                </tr>
            </thead>
            <tbody>
                @forelse($byStatus as $status => $count)
                    <tr class="border-b border-slate-100">
                        <td class="py-2 px-4">{{ OrderStatus::tryFrom($status)?->label() ?? $status }}</td>
                        <td class="py-2 px-4 text-right font-medium">{{ $count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-10 text-center text-slate-500">Bu aralıkta kayıt yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</details>

@if(isset($recentOrders))
    <details class="mt-10 rounded-xl border border-slate-200 bg-white text-sm shadow-sm max-w-5xl">
        <summary class="cursor-pointer select-none px-4 py-3 text-lg font-medium">
            Son siparişler
            <span class="text-sm font-normal text-slate-500">— {{ $recentOrders->total() }} kayıt</span>
        </summary>
        <div class="overflow-x-auto border-t border-slate-100">
            <form method="get" action="{{ route('restaurant.reports.index') }}" class="flex flex-wrap items-end justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50/50">
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
                <div class="text-xs text-slate-500">Sayfa: {{ $recentOrders->currentPage() }} / {{ $recentOrders->lastPage() }}</div>
            </form>
            <table class="w-full">
                <thead><tr class="border-b text-left text-slate-500"><th class="py-2 px-4">#</th><th>Müşteri</th><th>Ödeme</th><th>Durum</th><th>Kurye</th><th>Tutar</th></tr></thead>
                <tbody>
                    @foreach($recentOrders as $o)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 px-4"><a href="{{ route('restaurant.orders.show', $o) }}" class="text-amber-700 hover:underline">#{{ $o->id }}</a></td>
                            <td>{{ $o->customerDisplayName() }}</td>
                            <td class="text-slate-700">{{ $o->paymentMethodLabel() }}</td>
                            <td>{{ OrderStatus::tryFrom($o->status)?->label() ?? $o->status }}</td>
                            <td>{{ $o->courier?->name ?? '—' }}</td>
                            <td>{{ number_format((float) $o->total_price, 2) }} ₺</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100 bg-white">{{ $recentOrders->links() }}</div>
    </details>
@endif
@endsection
