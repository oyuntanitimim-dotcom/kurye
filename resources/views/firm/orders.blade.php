@php use App\Enums\OrderStatus; @endphp
@extends('layouts.firm')

@section('content')
<h1 class="text-2xl font-semibold text-slate-900 mb-6">Siparişler</h1>
<p id="firm-orders-stale-banner" class="mb-4 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950">
    Sipariş listesi güncellendi. <button type="button" id="firm-orders-reload" class="font-medium text-amber-900 underline hover:no-underline">Yenile</button>
</p>

<form method="get" action="{{ route('firm.orders.index') }}" class="mb-6 space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-wrap items-end gap-3 text-sm">
        <x-panel.searchable-select
            name="restaurant_id"
            label="İşletme"
            placeholder="İşletme ara…"
            :options="$restaurants->map(fn($r) => ['value' => $r->id, 'label' => $r->name])->all()"
            :value="($filters['restaurant_id'] ?? '')"
        />
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Durum</label>
            <select name="status" class="min-w-[12rem] rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-900 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                <option value="">Tümü</option>
                @foreach($statuses as $st)
                    <option value="{{ $st->value }}" @selected(($filters['status'] ?? '') === $st->value)>{{ $st->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Kurye</label>
            <select name="courier_id" class="min-w-[12rem] rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-900 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent" onchange="this.form.requestSubmit()">
                <option value="">Tümü</option>
                @foreach($couriers as $c)
                    @php $ao = (int) (($courierMeta[$c->id]['active_orders'] ?? 0)); @endphp
                    <option value="{{ $c->id }}" @selected((string) ($filters['courier_id'] ?? '') === (string) $c->id)>
                        {{ $c->name }}@if($ao > 0) (aktif: {{ $ao }})@endif
                    </option>
                @endforeach
            </select>
        </div>
        <label class="inline-flex items-center gap-2 pb-0.5 text-sm text-slate-700">
            <input type="checkbox" name="awaiting_courier" value="1" class="rounded border-slate-300" @checked(!empty($filters['awaiting_courier']))>
            <span>Hazır, restoran kurye çağırdı, atanmadı</span>
        </label>
        <button type="submit" class="rounded-lg bg-panel-accent px-4 py-2 text-sm font-medium text-white hover:opacity-95">Uygula</button>
        <a href="{{ route('firm.orders.index') }}" class="py-2 text-sm text-slate-600 hover:text-slate-900 hover:underline">Sıfırla</a>
    </div>
    <x-panel.table-toolbar
        :show-submit="false"
        search-placeholder="Sipariş no, müşteri, telefon veya firma adı…"
        :search-value="old('q', $filters['q'] ?? '')"
        :per-page-options="[10, 25, 30, 50]"
        :current-per-page="(int) ($filters['per_page'] ?? 30)"
    />
</form>

<div class="overflow-x-auto text-sm rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full">
        <thead><tr class="text-left border-b border-slate-100 text-slate-500"><th class="py-2 px-4">#</th><th class="py-2 px-4">Restoran</th><th class="py-2 px-4">Müşteri</th><th class="py-2 px-4">Ödeme</th><th class="py-2 px-4">Durum</th><th class="py-2 px-4">Kurye</th><th class="py-2 px-4">Ata / işlem</th></tr></thead>
        <tbody>
            @forelse($orders as $o)
                @php
                    $canFirmCancel = ! in_array($o->status, [OrderStatus::Delivered->value, OrderStatus::Cancelled->value], true);
                @endphp
                <tr class="border-b border-slate-100 align-top hover:bg-slate-50/80">
                    <td class="py-2 px-4"><a href="{{ route('firm.orders.show', $o) }}" class="font-medium text-panel-accent hover:underline">#{{ $o->id }}</a></td>
                    <td class="py-2 px-4">{{ $o->restaurant?->name }}</td>
                    <td class="py-2 px-4">{{ $o->customerDisplayName() }}</td>
                    <td class="py-2 px-4 text-slate-700">{{ $o->paymentMethodLabel() }}</td>
                    <td class="py-2 px-4">{{ OrderStatus::tryFrom($o->status)?->label() ?? $o->status }}</td>
                    <td class="py-2 px-4">{{ $o->courier?->name ?? '—' }}</td>
                    <td class="py-2 px-4">
                        @if($o->status === 'ready' && !$o->courier_id && $o->restaurant_courier_requested_at)
                            <form method="post" action="{{ route('firm.orders.assign', $o) }}" class="flex flex-wrap gap-2">
                                @csrf
                                @if(!empty($autoDispatchEnabled))
                                    <button
                                        type="submit"
                                        formaction="{{ route('firm.orders.auto_dispatch', $o) }}"
                                        class="rounded bg-slate-700 px-2 py-1 text-xs text-white hover:bg-slate-800"
                                    >
                                        Otomatik ata
                                    </button>
                                @endif
                                <select name="courier_id" class="rounded border border-slate-200 px-2 py-1 text-xs">
                                    @foreach($couriers as $c)
                                        @php $ao = (int) (($courierMeta[$c->id]['active_orders'] ?? 0)); @endphp
                                        <option value="{{ $c->id }}">{{ $c->name }}@if($ao > 0) (aktif: {{ $ao }})@endif</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="rounded bg-slate-900 px-2 py-1 text-xs text-white hover:bg-slate-800">Ata</button>
                            </form>
                        @elseif(in_array($o->status, [OrderStatus::CourierAssigned->value, OrderStatus::CourierAccepted->value], true))
                            <form method="post" action="{{ route('firm.orders.mark_delivered', $o) }}" onsubmit="return confirm('Sipariş #{{ $o->id }} teslim edildi olarak işaretlensin mi?');">
                                @csrf
                                <button type="submit" class="rounded bg-emerald-700 px-2 py-1 text-xs text-white hover:bg-emerald-800">
                                    Teslim edildi
                                </button>
                            </form>
                        @endif
                        @if($canFirmCancel)
                            <form method="post" action="{{ route('firm.orders.cancel', $o) }}" class="mt-2" onsubmit="return confirm('Sipariş #{{ $o->id }} iptal edilsin mi?');">
                                @csrf
                                <button type="submit" class="rounded bg-red-600 px-2 py-1 text-xs text-white hover:bg-red-700">İptal</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-slate-500">Kayıt bulunamadı.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@push('scripts')
<script>
(function () {
    var banner = document.getElementById('firm-orders-stale-banner');
    var btn = document.getElementById('firm-orders-reload');
    var t = null;
    function isOrdersListPath() {
        var p = window.location.pathname.replace(/\/$/, '') || '/';
        return /\/firma\/siparisler$/.test(p);
    }
    window.addEventListener('kurye-firm-board-changed', function () {
        if (!isOrdersListPath()) {
            return;
        }
        if (banner) {
            banner.classList.remove('hidden');
        }
        if (t) {
            clearTimeout(t);
        }
        t = setTimeout(function () {
            t = null;
            window.location.reload();
        }, 4000);
    });
    if (btn) {
        btn.addEventListener('click', function () {
            window.location.reload();
        });
    }
})();
</script>
@endpush
@endsection
