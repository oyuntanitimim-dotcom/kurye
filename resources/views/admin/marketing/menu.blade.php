@extends('layouts.admin')

@section('content')
<h1 class="mb-6 text-2xl font-semibold text-slate-900">Üst menü</h1>

@if(session('ok'))
    <p class="mb-4 rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('ok') }}</p>
@endif

<div class="mb-8 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <h2 class="mb-3 text-sm font-semibold text-slate-700">Yeni öğe</h2>
    <form method="post" action="{{ route('admin.marketing.menu.store') }}" class="flex flex-wrap items-end gap-3">
        @csrf
        <div>
            <label class="mb-1 block text-xs text-slate-500">Etiket</label>
            <input name="label" required class="rounded border border-slate-300 px-2 py-1.5 text-sm" />
        </div>
        <div class="min-w-[12rem] flex-1">
            <label class="mb-1 block text-xs text-slate-500">URL</label>
            <input name="url" required class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm" placeholder="#cozumler veya https://..." />
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="open_in_new_tab" value="1" /> Yeni sekme</label>
        <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white">Ekle</button>
    </form>
</div>

<div class="space-y-4">
    @foreach($items as $item)
        <form method="post" action="{{ route('admin.marketing.menu.update', $item) }}" class="flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-white p-4">
            @csrf
            @method('PUT')
            <div>
                <label class="mb-1 block text-xs text-slate-500">Etiket</label>
                <input name="label" value="{{ $item->label }}" required class="rounded border border-slate-300 px-2 py-1.5 text-sm" />
            </div>
            <div class="min-w-[12rem] flex-1">
                <label class="mb-1 block text-xs text-slate-500">URL</label>
                <input name="url" value="{{ $item->url }}" required class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm" />
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-500">Sıra</label>
                <input name="sort_order" type="number" value="{{ $item->sort_order }}" class="w-20 rounded border border-slate-300 px-2 py-1.5 text-sm" />
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="open_in_new_tab" value="1" @checked($item->open_in_new_tab) /> Yeni sekme</label>
            <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white">Kaydet</button>
        </form>
        <form method="post" action="{{ route('admin.marketing.menu.destroy', $item) }}" class="-mt-2 mb-4 ml-4" onsubmit="return confirm('Silinsin mi?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-red-600 hover:underline">Sil</button>
        </form>
    @endforeach
</div>

<p class="mt-6"><a href="{{ route('admin.marketing.index') }}" class="text-sm text-amber-700 hover:underline">← CMS özet</a></p>
@endsection
