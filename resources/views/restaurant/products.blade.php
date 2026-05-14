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
        <h1 class="text-2xl font-semibold text-slate-900">Ürünler</h1>
        <p class="mt-1 text-sm text-slate-600">Bu ekrandan ürünlerinizi yönetebilirsiniz.</p>
    </div>
    <div class="flex flex-wrap items-center justify-end gap-2">
        <label class="sr-only">Kanal özeti</label>
        <select class="min-w-[14rem] cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700" disabled title="Özet bilgi">
            <option>Telefon siparişi ({{ $phoneOrderCount }})</option>
        </select>
    </div>
</div>

<div class="mb-4 flex flex-wrap items-center justify-end gap-2">
    <form method="post" action="{{ route('restaurant.products.copy-all') }}" class="inline" onsubmit="return confirm('Tüm ürünler kopyalanacak. Devam?');">
        @csrf
        <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50">
            <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.015 9.015 0 016.748-2.63 9.018 9.018 0 013.241.679A9.01 9.01 0 0118 9.75c0 2.592-.684 5.023-1.88 7.125M15.75 17.25h1.5a1.125 1.125 0 001.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" /></svg>
            Tümünü kopyala
        </button>
    </form>
    <form method="post" action="{{ route('restaurant.products.destroy-all') }}" class="inline" onsubmit="return confirm('Siparişi olmayan tüm ürünler silinecek. Emin misiniz?');">
        @csrf
        <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
            Tümünü sil
        </button>
    </form>
    <button type="submit" form="products-bulk-form" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50">
        <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V6m-7.5-2.25v2.25m-4.5 3.75h18m-15.75 3.75h12m-12 3h12m-12 3h12m-12 3h12M4.5 19.5h15a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5h-15A1.5 1.5 0 003 4.5v14a1.5 1.5 0 001.5 1.5z" /></svg>
        Toplu kaydet
    </button>
    <button type="button" id="open-product-modal" class="inline-flex items-center gap-1 rounded-lg bg-panel-accent px-4 py-2 text-sm font-medium text-white shadow-sm hover:opacity-95">
        Yeni ürün ekle +
    </button>
</div>

<form method="get" action="{{ route('restaurant.products.index') }}" class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
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
                Sütunlar
            </summary>
            <div class="absolute right-0 z-20 mt-1 w-56 rounded-lg border border-slate-200 bg-white p-3 text-sm shadow-lg">
                <p class="mb-2 text-xs font-medium text-slate-500">Göster</p>
                <div class="space-y-2" id="product-column-toggles">
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="image" checked> Resim</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="name" checked> Ürün adı</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="price" checked> Satış fiyatı</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="discounted" checked> İndirimli fiyat</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="category" checked> Kategori</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="prep" checked> Hazırlık</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="stock" checked> Stok</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300" data-col-toggle="actions" checked> İşlem</label>
                </div>
            </div>
        </details>
    </div>
</form>

<form id="products-bulk-form" method="post" action="{{ route('restaurant.products.bulk-update') }}">
    @csrf
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-[960px] text-sm" id="products-table">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="py-3 pl-4 pr-2" data-col="image">Resim</th>
                    <th class="px-2 py-3" data-col="name">Ürün adı</th>
                    <th class="px-2 py-3 whitespace-nowrap" data-col="price">Satış fiyatı</th>
                    <th class="px-2 py-3 whitespace-nowrap" data-col="discounted">İndirimli</th>
                    <th class="px-2 py-3 min-w-[8rem]" data-col="category">Kategori</th>
                    <th class="px-2 py-3 whitespace-nowrap" data-col="prep">Hazırlık</th>
                    <th class="px-2 py-3" data-col="stock">Stok</th>
                    <th class="px-4 py-3 text-right" data-col="actions">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $p)
                    <tr class="border-b border-slate-100 align-top hover:bg-slate-50/80" data-product-row="{{ $p->id }}">
                        <td class="py-3 pl-4 pr-2" data-col="image">
                            <a href="{{ route('restaurant.products.edit', $p) }}" data-open-product-edit class="block h-14 w-14 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                @if($p->imageUrl())
                                    <img src="{{ $p->imageUrl() }}" alt="" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3A1.5 1.5 0 001.5 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                                    </span>
                                @endif
                            </a>
                        </td>
                        <td class="px-2 py-3" data-col="name">
                            <input type="text" name="products[{{ $p->id }}][name]" value="{{ $p->name }}" required maxlength="190" class="w-full min-w-[12rem] rounded-lg border border-slate-200 px-2 py-1.5 font-medium text-slate-900 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                        </td>
                        <td class="px-2 py-3" data-col="price">
                            <div class="flex items-center gap-1">
                                <input type="number" step="0.01" min="0" name="products[{{ $p->id }}][price]" value="{{ $p->price }}" required class="w-24 rounded-lg border border-slate-200 px-2 py-1.5 tabular-nums focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                                <span class="text-slate-500">₺</span>
                            </div>
                        </td>
                        <td class="px-2 py-3" data-col="discounted">
                            <div class="flex items-center gap-1">
                                <input type="number" step="0.01" min="0" name="products[{{ $p->id }}][discounted_price]" value="{{ $p->discounted_price }}" placeholder="—" class="w-24 rounded-lg border border-slate-200 px-2 py-1.5 tabular-nums focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                                <span class="text-slate-500">₺</span>
                            </div>
                        </td>
                        <td class="px-2 py-3" data-col="category">
                            <select name="products[{{ $p->id }}][category_id]" class="w-full max-w-[12rem] rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                                <option value="">—</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" @selected($p->category_id === $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-2 py-3" data-col="prep">
                            <div class="flex items-center gap-1">
                                <input type="number" min="0" max="1440" name="products[{{ $p->id }}][prep_time_minutes]" value="{{ $p->prep_time_minutes }}" placeholder="—" class="w-16 rounded-lg border border-slate-200 px-2 py-1.5 tabular-nums focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                                <span class="text-xs text-slate-500">dk</span>
                            </div>
                        </td>
                        <td class="px-2 py-3" data-col="stock">
                            <input type="number" min="0" name="products[{{ $p->id }}][stock]" value="{{ $p->stock }}" required class="w-20 rounded-lg border border-slate-200 px-2 py-1.5 tabular-nums focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                        </td>
                        <td class="px-4 py-3 text-right" data-col="actions">
                            <details class="relative inline-block text-left">
                                <summary class="cursor-pointer list-none rounded-md border border-slate-200 bg-white px-2 py-1 text-slate-600 hover:bg-slate-50 [&::-webkit-details-marker]:hidden">⋯</summary>
                                <div class="absolute right-0 z-10 mt-1 w-40 rounded-lg border border-slate-200 bg-white py-1 text-left shadow-lg">
                                    <a href="{{ route('restaurant.products.edit', $p) }}" data-open-product-edit class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Detaylı düzenle</a>
                                </div>
                            </details>
                            <form method="post" action="{{ route('restaurant.products.destroy', $p) }}" class="mt-2 inline" onsubmit="return confirm('Bu ürün silinsin mi?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-red-600 hover:underline">Sil</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-slate-500">Kayıt bulunamadı.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</form>

<div class="mt-4">{{ $products->links() }}</div>

<dialog id="product-edit-modal" class="w-[min(1100px,95vw)] rounded-2xl border border-slate-200 p-0 shadow-2xl backdrop:bg-black/40">
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3">
        <div class="min-w-0">
            <h2 class="truncate text-lg font-semibold text-slate-900">Ürün detayları</h2>
            <p class="mt-0.5 text-sm text-slate-600">Düzenlemeyi popup içinde yap.</p>
        </div>
        <button type="button" id="close-product-edit-modal" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 hover:bg-slate-50">Kapat</button>
    </div>
    <div class="h-[80vh] bg-white">
        <iframe id="product-edit-iframe" src="about:blank" class="h-full w-full" referrerpolicy="no-referrer"></iframe>
    </div>
</dialog>

<dialog id="product-create-modal" class="w-full max-w-xl rounded-2xl border border-slate-200 p-0 shadow-2xl backdrop:bg-black/40">
    <div class="border-b border-slate-100 px-5 py-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Yeni ürün</h2>
                <p class="mt-0.5 text-sm text-slate-600">Sayfadan ayrılmadan hızlı ekle.</p>
            </div>
            <button type="button" id="close-product-modal" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 hover:bg-slate-50">Kapat</button>
        </div>
    </div>

    <form method="post" action="{{ route('restaurant.products.store') }}" enctype="multipart/form-data" class="space-y-4 px-5 py-4" id="product-create-form">
        @csrf
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Ad</label>
            <input name="name" required maxlength="190" class="w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Satış fiyatı</label>
                <input type="number" step="0.01" min="0" name="price" required class="w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">İndirimli fiyat</label>
                <input type="number" step="0.01" min="0" name="discounted_price" placeholder="—" class="w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Kategori</label>
                <select name="category_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                    <option value="">—</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Hazırlık (dk)</label>
                <input type="number" min="0" max="1440" name="prep_time_minutes" placeholder="—" class="w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Stok</label>
                <input type="number" min="0" name="stock" value="0" required class="w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Durum</label>
                <select name="status" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                    <option value="active" selected>Aktif</option>
                    <option value="inactive">Pasif</option>
                </select>
            </div>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Açıklama</label>
            <textarea name="description" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent"></textarea>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Görsel</label>
            <input type="file" name="image" accept="image/*" class="w-full text-sm text-slate-600">
            <p class="mt-1 text-xs text-slate-500">PNG/JPG önerilir. Yüklersen listede logo olarak görünür.</p>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
            <button type="button" id="cancel-product-modal" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50">Vazgeç</button>
            <button type="submit" class="rounded-lg bg-panel-accent px-5 py-2 text-sm font-medium text-white hover:opacity-95">Kaydet</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
(function () {
    var key = 'kurye_restaurant_product_cols';
    var defaults = { image: true, name: true, price: true, discounted: true, category: true, prep: true, stock: true, actions: true };

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
        document.querySelectorAll('#products-table [data-col]').forEach(function (el) {
            var c = el.getAttribute('data-col');
            if (!c || state[c] === undefined) return;
            el.classList.toggle('hidden', !state[c]);
        });
        document.querySelectorAll('#product-column-toggles [data-col-toggle]').forEach(function (cb) {
            var c = cb.getAttribute('data-col-toggle');
            if (state[c] !== undefined) cb.checked = state[c];
        });
    }

    var state = load();
    apply(state);

    document.querySelectorAll('#product-column-toggles [data-col-toggle]').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var c = cb.getAttribute('data-col-toggle');
            state[c] = cb.checked;
            save(state);
            apply(state);
        });
    });
})();
</script>

<script>
(function () {
    var modal = document.getElementById('product-create-modal');
    var openBtn = document.getElementById('open-product-modal');
    var closeBtn = document.getElementById('close-product-modal');
    var cancelBtn = document.getElementById('cancel-product-modal');

    if (!modal || !openBtn) return;

    function open() {
        if (typeof modal.showModal === 'function') {
            modal.showModal();
        } else {
            modal.setAttribute('open', 'open');
        }
        var first = modal.querySelector('input[name="name"]');
        if (first) first.focus();
    }

    function close() {
        if (typeof modal.close === 'function') {
            modal.close();
        } else {
            modal.removeAttribute('open');
        }
    }

    openBtn.addEventListener('click', open);
    if (closeBtn) closeBtn.addEventListener('click', close);
    if (cancelBtn) cancelBtn.addEventListener('click', close);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) close();
    });
})();
</script>

<script>
(function () {
    var modal = document.getElementById('product-edit-modal');
    var iframe = document.getElementById('product-edit-iframe');
    var closeBtn = document.getElementById('close-product-edit-modal');
    if (!modal || !iframe) return;

    function openWithUrl(url) {
        iframe.src = url;
        if (typeof modal.showModal === 'function') {
            modal.showModal();
        } else {
            modal.setAttribute('open', 'open');
        }
    }

    function close() {
        if (typeof modal.close === 'function') {
            modal.close();
        } else {
            modal.removeAttribute('open');
        }
        iframe.src = 'about:blank';
    }

    document.querySelectorAll('[data-open-product-edit]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var url = a.getAttribute('href');
            if (!url) return;
            openWithUrl(url);
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', close);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) close();
    });
})();
</script>
@endpush
@endsection
