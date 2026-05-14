@extends('layouts.restaurant')

@section('content')
@if($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <ul class="list-disc pl-5 space-y-1">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mb-2 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Müşteriler</h1>
        <p class="mt-1 text-sm text-slate-600">Bu ekrandan müşterilerinizi ve sipariş yoğunluğunu izleyebilirsiniz.</p>
    </div>
    <div class="flex flex-wrap items-center justify-end gap-2">
        <label class="sr-only">Özet</label>
        <select class="min-w-[18rem] cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700" disabled title="Özet bilgi">
            <option>Müşteri ({{ (int) ($summary['customer_count'] ?? 0) }}) • Sipariş ({{ (int) ($summary['order_count'] ?? 0) }}) • Toplam {{ number_format((float) ($summary['total_spent'] ?? 0), 2, ',', '.') }} ₺</option>
        </select>
    </div>
</div>

<div class="mb-4 flex flex-wrap items-center justify-end gap-2">
    <a href="{{ route('restaurant.customers.export', request()->only(['q','per_page','type','min_orders','from','to','sort','dir'])) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50">
        <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-6L12 3m0 0l4.5 7.5M12 3v13.5" /></svg>
        CSV indir
    </a>
    <form method="post" action="{{ route('restaurant.customers.destroy-all') }}" class="inline" onsubmit="return confirm('Siparişi olmayan tüm müşteriler silinecek. Emin misiniz?');">
        @csrf
        <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
            Tüm müşterileri sil
        </button>
    </form>

    <details class="relative">
        <summary class="cursor-pointer list-none rounded-lg bg-panel-accent px-4 py-2 text-sm font-medium text-white shadow-sm hover:opacity-95 [&::-webkit-details-marker]:hidden">
            Müşteri ekle +
        </summary>
        <div class="absolute right-0 z-20 mt-2 w-[22rem] rounded-xl border border-slate-200 bg-white p-4 text-sm shadow-lg">
            <form method="post" action="{{ route('restaurant.customers.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Ad</label>
                    <input type="text" name="name" required maxlength="190" class="w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Telefon</label>
                    <input type="text" name="phone" maxlength="32" class="w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Adres</label>
                    <textarea name="address" rows="3" maxlength="2000" class="w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Kaydet</button>
                </div>
            </form>
        </div>
    </details>
</div>

<form method="get" action="{{ route('restaurant.customers.index') }}" class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0 flex-1">
            <x-panel.table-toolbar
                with-search-icon
                search-placeholder="Ara (ad / telefon)"
                :search-value="old('q', $filters['q'] ?? '')"
                :per-page-options="[10, 25, 30, 50]"
                :current-per-page="(int) ($filters['per_page'] ?? 25)"
            />
        </div>
        <details class="relative shrink-0">
            <summary class="cursor-pointer list-none rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
                Filtreler
            </summary>
            <div class="absolute right-0 z-20 mt-1 w-[22rem] rounded-lg border border-slate-200 bg-white p-3 text-sm shadow-lg">
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="mb-1 block text-xs font-medium text-slate-500">Tür</label>
                        <select name="type" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                            <option value="all" @selected(($filters['type'] ?? 'all') === 'all')>Tümü</option>
                            <option value="registered" @selected(($filters['type'] ?? '') === 'registered')>Kayıtlı</option>
                            <option value="guest" @selected(($filters['type'] ?? '') === 'guest')>Siparişten türetilmiş</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Min sipariş</label>
                        <input type="number" min="0" name="min_orders" value="{{ (int) ($filters['min_orders'] ?? 0) }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Sıralama</label>
                        <select name="sort" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                            <option value="last_order_at" @selected(($filters['sort'] ?? 'last_order_at') === 'last_order_at')>Son sipariş</option>
                            <option value="order_count" @selected(($filters['sort'] ?? '') === 'order_count')>Sipariş sayısı</option>
                            <option value="total_spent" @selected(($filters['sort'] ?? '') === 'total_spent')>Toplam</option>
                            <option value="name" @selected(($filters['sort'] ?? '') === 'name')>Ad</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Başlangıç</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Bitiş</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                    </div>
                    <div class="col-span-2">
                        <label class="mb-1 block text-xs font-medium text-slate-500">Yön</label>
                        <select name="dir" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                            <option value="desc" @selected(($filters['dir'] ?? 'desc') === 'desc')>Azalan</option>
                            <option value="asc" @selected(($filters['dir'] ?? '') === 'asc')>Artan</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 flex justify-end gap-2">
                    <a href="{{ route('restaurant.customers.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50">Sıfırla</a>
                    <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white">Uygula</button>
                </div>
            </div>
        </details>
        <details class="relative shrink-0">
            <summary class="cursor-pointer list-none rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
                Sütunlar
            </summary>
            <div class="absolute right-0 z-20 mt-1 w-56 rounded-lg border border-slate-200 bg-white p-3 text-sm shadow-lg">
                <p class="mb-2 text-xs font-medium text-slate-500">Göster</p>
                <div class="space-y-2" id="customer-column-toggles">
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="customer" checked> Müşteri</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="count" checked> Sipariş sayısı</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="spent" checked> Toplam</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="last" checked> Son sipariş</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="phone" checked> Telefon</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="neighborhood" checked> Mahalle</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="actions" checked> İşlem</label>
                </div>
            </div>
        </details>
    </div>
</form>

<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full min-w-[860px] text-sm" id="customers-table">
        <thead>
            <tr class="border-b border-slate-100 bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <th class="py-3 pl-4 pr-2" data-col="customer">Müşteri</th>
                <th class="px-2 py-3 whitespace-nowrap" data-col="count">
                    <a class="hover:underline" href="{{ route('restaurant.customers.index', array_merge(request()->except('page'), ['sort' => 'order_count', 'dir' => ($filters['sort'] ?? 'last_order_at') === 'order_count' && ($filters['dir'] ?? 'desc') === 'desc' ? 'asc' : 'desc'])) }}">
                        Sipariş sayısı
                    </a>
                </th>
                <th class="px-2 py-3 whitespace-nowrap" data-col="spent">
                    <a class="hover:underline" href="{{ route('restaurant.customers.index', array_merge(request()->except('page'), ['sort' => 'total_spent', 'dir' => ($filters['sort'] ?? '') === 'total_spent' && ($filters['dir'] ?? 'desc') === 'desc' ? 'asc' : 'desc'])) }}">
                        Toplam
                    </a>
                </th>
                <th class="px-2 py-3 whitespace-nowrap" data-col="last">
                    <a class="hover:underline" href="{{ route('restaurant.customers.index', array_merge(request()->except('page'), ['sort' => 'last_order_at', 'dir' => ($filters['sort'] ?? 'last_order_at') === 'last_order_at' && ($filters['dir'] ?? 'desc') === 'desc' ? 'asc' : 'desc'])) }}">
                        Son sipariş
                    </a>
                </th>
                <th class="px-2 py-3 whitespace-nowrap" data-col="phone">Telefon</th>
                <th class="px-2 py-3 whitespace-nowrap" data-col="neighborhood">Mahalle</th>
                <th class="px-4 py-3 text-right" data-col="actions">İşlem</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $c)
                <tr class="border-b border-slate-100 align-top hover:bg-slate-50/80">
                    <td class="py-3 pl-4 pr-2" data-col="customer">
                        <div class="font-medium text-slate-900">{{ $c->name ?: '—' }}</div>
                        @if(!empty($c->user_id))
                            <div class="mt-0.5 text-xs text-slate-500">Kayıtlı</div>
                        @else
                            <div class="mt-0.5 text-xs text-slate-500">Siparişten türetilmiş</div>
                        @endif
                    </td>
                    <td class="px-2 py-3" data-col="count">
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ (int) $c->order_count }}</span>
                    </td>
                    <td class="px-2 py-3" data-col="spent">
                        <span class="tabular-nums text-slate-800">{{ number_format((float) ($c->total_spent ?? 0), 2, ',', '.') }} ₺</span>
                    </td>
                    <td class="px-2 py-3" data-col="last">
                        <span class="text-slate-700">{{ $c->last_order_at ? \Illuminate\Support\Carbon::parse($c->last_order_at)->format('d.m.Y H:i') : '—' }}</span>
                    </td>
                    <td class="px-2 py-3" data-col="phone">
                        <span class="text-slate-800">{{ $c->phone ?: '—' }}</span>
                    </td>
                    <td class="px-2 py-3" data-col="neighborhood">
                        @php($k = (string) ($c->customer_key ?? ''))
                        <span class="text-slate-700">{{ $neighborhoodByKey[$k] ?? '—' }}</span>
                    </td>
                    <td class="px-4 py-3 text-right" data-col="actions">
                        <details class="relative inline-block text-left">
                            <summary class="cursor-pointer list-none rounded-md border border-slate-200 bg-white px-2 py-1 text-slate-600 hover:bg-slate-50 [&::-webkit-details-marker]:hidden">⋯</summary>
                            <div class="absolute right-0 z-10 mt-1 w-44 rounded-lg border border-slate-200 bg-white py-1 text-left shadow-lg">
                                <a href="{{ route('restaurant.orders.index', array_filter(['q' => $c->phone, 'per_page' => request('per_page', 25)])) }}" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Siparişleri gör</a>
                            </div>
                        </details>
                        @if(!empty($c->user_id) && isset($deletableUserIds[(int) $c->user_id]))
                            <form method="post" action="{{ route('restaurant.customers.destroy', (int) $c->user_id) }}" class="inline" onsubmit="return confirm('Bu müşteri silinsin mi?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-red-600 hover:underline">Sil</button>
                            </form>
                        @else
                            <span class="text-xs text-slate-400">—</span>
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

<div class="mt-4">{{ $customers->links() }}</div>

@push('scripts')
<script>
(function () {
    var key = 'kurye_restaurant_customer_cols';
    var defaults = { customer: true, count: true, spent: true, last: true, phone: true, neighborhood: true, actions: true };

    function load() {
        try {
            var s = localStorage.getItem(key);
            if (!s) return defaults;
            return Object.assign({}, defaults, JSON.parse(s));
        } catch (e) {
            return defaults;
        }
    }

    function save(state) {
        localStorage.setItem(key, JSON.stringify(state));
    }

    function apply(state) {
        document.querySelectorAll('#customers-table [data-col]').forEach(function (el) {
            var c = el.getAttribute('data-col');
            if (!c || state[c] === undefined) return;
            el.classList.toggle('hidden', !state[c]);
        });
        document.querySelectorAll('#customer-column-toggles [data-col-toggle]').forEach(function (cb) {
            var c = cb.getAttribute('data-col-toggle');
            if (state[c] !== undefined) cb.checked = state[c];
        });
    }

    var state = load();
    apply(state);

    document.querySelectorAll('#customer-column-toggles [data-col-toggle]').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var c = cb.getAttribute('data-col-toggle');
            state[c] = cb.checked;
            save(state);
            apply(state);
        });
    });
})();
</script>
@endpush
@endsection

