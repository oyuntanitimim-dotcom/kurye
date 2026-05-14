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
        <h1 class="text-2xl font-semibold text-slate-900">Kategoriler</h1>
        <p class="mt-1 text-sm text-slate-600">Bu ekrandan kategorilerinizi yönetebilirsiniz.</p>
    </div>
    <div class="flex flex-wrap items-center justify-end gap-2">
        <label class="sr-only">Özet</label>
        <select class="min-w-[14rem] cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700" disabled title="Özet bilgi">
            <option>
                Kategori ({{ (int) ($summary['category_count'] ?? 0) }}) •
                Kategorilerdeki ürün ({{ (int) ($summary['products_in_categories'] ?? 0) }}) •
                Toplam ürün ({{ (int) ($summary['product_count'] ?? 0) }})
            </option>
        </select>
    </div>
</div>

<div class="mb-4 flex flex-wrap items-center justify-end gap-2">
    <a href="{{ route('restaurant.categories.export', request()->only(['q','per_page','has_products','min_products','from','to','sort','dir'])) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50">
        <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-6L12 3m0 0l4.5 7.5M12 3v13.5" /></svg>
        CSV indir
    </a>
    <form method="post" action="{{ route('restaurant.categories.destroy-all') }}" class="inline" onsubmit="return confirm('Ürünü olmayan tüm kategoriler silinecek. Emin misiniz?');">
        @csrf
        <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
            Tümünü sil
        </button>
    </form>
    <button type="submit" form="categories-bulk-form" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50">
        <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V6m-7.5-2.25v2.25m-4.5 3.75h18m-15.75 3.75h12m-12 3h12m-12 3h12m-12 3h12M4.5 19.5h15a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5h-15A1.5 1.5 0 003 4.5v14a1.5 1.5 0 001.5 1.5z" /></svg>
        Toplu kaydet
    </button>
    <form method="post" action="{{ route('restaurant.categories.store') }}" class="inline-flex items-center gap-2">
        @csrf
        <input type="text" name="name" placeholder="Yeni kategori" required maxlength="120" class="w-56 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
        <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-panel-accent px-4 py-2 text-sm font-medium text-white shadow-sm hover:opacity-95">
            Yeni kategori ekle +
        </button>
    </form>
</div>

<form method="get" action="{{ route('restaurant.categories.index') }}" class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0 flex-1">
            <x-panel.table-toolbar
                with-search-icon
                search-placeholder="Ara"
                :search-value="old('q', $filters['q'] ?? '')"
                :per-page-options="[10, 25, 30, 50]"
                :current-per-page="(int) ($filters['per_page'] ?? 30)"
            />
        </div>
        <details class="relative shrink-0">
            <summary class="cursor-pointer list-none rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
                Filtreler
            </summary>
            <div class="absolute right-0 z-20 mt-1 w-[22rem] rounded-lg border border-slate-200 bg-white p-3 text-sm shadow-lg">
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="mb-1 block text-xs font-medium text-slate-500">Ürün durumu</label>
                        <select name="has_products" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                            <option value="all" @selected(($filters['has_products'] ?? 'all') === 'all')>Tümü</option>
                            <option value="with" @selected(($filters['has_products'] ?? '') === 'with')>Ürünü olan</option>
                            <option value="without" @selected(($filters['has_products'] ?? '') === 'without')>Ürünü olmayan</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Min ürün</label>
                        <input type="number" min="0" name="min_products" value="{{ (int) ($filters['min_products'] ?? 0) }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Sıralama</label>
                        <select name="sort" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                            <option value="sort_order" @selected(($filters['sort'] ?? 'sort_order') === 'sort_order')>Sıra</option>
                            <option value="name" @selected(($filters['sort'] ?? '') === 'name')>Ad</option>
                            <option value="products_count" @selected(($filters['sort'] ?? '') === 'products_count')>Ürün sayısı</option>
                            <option value="created_at" @selected(($filters['sort'] ?? '') === 'created_at')>Oluşturma</option>
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
                            <option value="asc" @selected(($filters['dir'] ?? 'asc') === 'asc')>Artan</option>
                            <option value="desc" @selected(($filters['dir'] ?? '') === 'desc')>Azalan</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 flex justify-end gap-2">
                    <a href="{{ route('restaurant.categories.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50">Sıfırla</a>
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
                <div class="space-y-2" id="category-column-toggles">
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="name" checked> Kategori adı</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="sort" checked> Sıra</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="count" checked> Ürün sayısı</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="created" checked> Oluşturma</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="actions" checked> İşlem</label>
                </div>
            </div>
        </details>
    </div>
</form>

<form id="categories-bulk-form" method="post" action="{{ route('restaurant.categories.bulk-update') }}">
    @csrf
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-[760px] text-sm" id="categories-table">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="py-3 pl-4 pr-2" data-col="name">Kategori adı</th>
                    <th class="px-2 py-3 whitespace-nowrap" data-col="sort">
                        <a class="hover:underline" href="{{ route('restaurant.categories.index', array_merge(request()->except('page'), ['sort' => 'sort_order', 'dir' => ($filters['sort'] ?? 'sort_order') === 'sort_order' && ($filters['dir'] ?? 'asc') === 'asc' ? 'desc' : 'asc'])) }}">
                            Sıra
                        </a>
                    </th>
                    <th class="px-2 py-3 whitespace-nowrap" data-col="count">
                        <a class="hover:underline" href="{{ route('restaurant.categories.index', array_merge(request()->except('page'), ['sort' => 'products_count', 'dir' => ($filters['sort'] ?? '') === 'products_count' && ($filters['dir'] ?? 'asc') === 'asc' ? 'desc' : 'asc'])) }}">
                            Ürün sayısı
                        </a>
                    </th>
                    <th class="px-2 py-3 whitespace-nowrap" data-col="created">
                        <a class="hover:underline" href="{{ route('restaurant.categories.index', array_merge(request()->except('page'), ['sort' => 'created_at', 'dir' => ($filters['sort'] ?? '') === 'created_at' && ($filters['dir'] ?? 'asc') === 'asc' ? 'desc' : 'asc'])) }}">
                            Oluşturma
                        </a>
                    </th>
                    <th class="px-4 py-3 text-right" data-col="actions">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $c)
                    <tr class="border-b border-slate-100 align-top hover:bg-slate-50/80">
                        <td class="py-3 pl-4 pr-2" data-col="name">
                            <input type="text" name="categories[{{ $c->id }}][name]" value="{{ $c->name }}" required maxlength="120" class="w-full min-w-[14rem] rounded-lg border border-slate-200 px-2 py-1.5 font-medium text-slate-900 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                        </td>
                        <td class="px-2 py-3" data-col="sort">
                            <input type="number" min="0" max="65535" name="categories[{{ $c->id }}][sort_order]" value="{{ $c->sort_order }}" class="w-24 rounded-lg border border-slate-200 px-2 py-1.5 tabular-nums focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                        </td>
                        <td class="px-2 py-3" data-col="count">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ $c->products_count }}</span>
                        </td>
                        <td class="px-2 py-3" data-col="created">
                            <span class="text-slate-700">{{ $c->created_at?->format('d.m.Y') ?? '—' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right" data-col="actions">
                            <form method="post" action="{{ route('restaurant.categories.destroy', $c) }}" class="inline" onsubmit="return confirm('Bu kategori silinsin mi?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-red-600 hover:underline">Sil</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-slate-500">Kayıt bulunamadı.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</form>

<div class="mt-4">{{ $categories->links() }}</div>

@push('scripts')
<script>
(function () {
    var key = 'kurye_restaurant_category_cols';
    var defaults = { name: true, sort: true, count: true, created: true, actions: true };

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
        document.querySelectorAll('#categories-table [data-col]').forEach(function (el) {
            var c = el.getAttribute('data-col');
            if (!c || state[c] === undefined) return;
            el.classList.toggle('hidden', !state[c]);
        });
        document.querySelectorAll('#category-column-toggles [data-col-toggle]').forEach(function (cb) {
            var c = cb.getAttribute('data-col-toggle');
            if (state[c] !== undefined) cb.checked = state[c];
        });
    }

    var state = load();
    apply(state);

    document.querySelectorAll('#category-column-toggles [data-col-toggle]').forEach(function (cb) {
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
