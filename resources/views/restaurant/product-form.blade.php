@extends('layouts.restaurant')

@section('content')
<div class="max-w-lg">
    <h1 class="text-2xl font-semibold mb-6 text-slate-900">{{ $title }}</h1>
    @php($imgs = $product->exists ? ($product->images ?? collect()) : collect())
    <form method="post" action="{{ $product->exists ? route('restaurant.products.update', $product) : route('restaurant.products.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @if($product->exists)
            @method('PUT')
        @endif
        <div>
            <label class="text-sm text-slate-600">Ad</label>
            <input name="name" value="{{ old('name', $product->name) }}" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
        </div>
        <div>
            <label class="text-sm text-slate-600">Açıklama</label>
            <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">{{ old('description', $product->description) }}</textarea>
        </div>
        <div>
            <label class="text-sm text-slate-600">Kategori</label>
            <select name="category_id" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                <option value="">—</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id)==$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm text-slate-600">Satış fiyatı</label>
            <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
        </div>
        <div>
            <label class="text-sm text-slate-600">İndirimli fiyat (isteğe bağlı)</label>
            <input type="number" step="0.01" min="0" name="discounted_price" value="{{ old('discounted_price', $product->discounted_price) }}" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
            <p class="mt-1 text-xs text-slate-500">Boş bırakılırsa liste fiyatı geçerli olur.</p>
        </div>
        <div>
            <label class="text-sm text-slate-600">Hazırlık süresi (dk)</label>
            <input type="number" min="0" max="1440" name="prep_time_minutes" value="{{ old('prep_time_minutes', $product->prep_time_minutes) }}" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
        </div>
        <div>
            <label class="text-sm text-slate-600">Görseller</label>

            @if($product->exists)
                @if($imgs->isNotEmpty() || $product->imageUrl())
                    <div class="mt-2 grid grid-cols-4 gap-2">
                        @foreach($imgs as $img)
                            <div class="relative">
                                <img src="{{ $img->url() }}" alt="" class="h-20 w-20 rounded-lg border border-slate-200 object-cover">
                                @if($img->is_primary)
                                    <span class="absolute left-1 top-1 rounded bg-slate-900/80 px-1.5 py-0.5 text-[10px] font-medium text-white">Logo</span>
                                @endif
                                <button
                                    type="submit"
                                    form="delete-image-{{ $img->id }}"
                                    class="absolute right-1 top-1 rounded bg-white/90 px-1.5 py-0.5 text-[10px] font-medium text-red-700 hover:bg-white"
                                    onclick="return confirm('Görsel silinsin mi?');"
                                >Sil</button>
                            </div>
                        @endforeach

                        @if($imgs->isEmpty() && $product->imageUrl())
                            <div class="relative">
                                <img src="{{ $product->imageUrl() }}" alt="" class="h-20 w-20 rounded-lg border border-slate-200 object-cover">
                                <span class="absolute left-1 top-1 rounded bg-slate-900/80 px-1.5 py-0.5 text-[10px] font-medium text-white">Logo</span>
                                <p class="mt-1 text-[11px] text-slate-500">Eski görsel</p>
                            </div>
                        @endif
                    </div>
                @endif
            @endif

            <div class="mt-2 space-y-2">
                <div>
                    <label class="block text-xs font-medium text-slate-500">Logo (tek)</label>
                    <input type="file" name="image" accept="image/*" class="mt-1 w-full text-sm text-slate-600">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500">Galeri (çoklu)</label>
                    <input type="file" name="images[]" accept="image/*" multiple class="mt-1 w-full text-sm text-slate-600">
                    <p class="mt-1 text-xs text-slate-500">Birden fazla resim seçebilirsin. İlk eklenen, logo yoksa otomatik logo olur.</p>
                </div>
            </div>
        </div>
        <div>
            <label class="text-sm text-slate-600">Stok</label>
            <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
        </div>
        <div>
            <label class="text-sm text-slate-600">Durum</label>
            <select name="status" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent">
                <option value="active" @selected(old('status', $product->status)==='active')>Aktif</option>
                <option value="inactive" @selected(old('status', $product->status)==='inactive')>Pasif</option>
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-panel-accent px-6 py-2 text-white font-medium hover:opacity-95">Kaydet</button>
    </form>

    @if($product->exists && $imgs->isNotEmpty())
        @foreach($imgs as $img)
            <form id="delete-image-{{ $img->id }}" method="post" action="{{ route('restaurant.products.images.destroy', [$product, $img]) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endif
</div>
@endsection
