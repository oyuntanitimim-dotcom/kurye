@extends('layouts.admin')

@section('content')
<h1 class="mb-6 text-2xl font-semibold text-slate-900">{{ $slide->exists ? 'Slayt düzenle' : 'Yeni slayt' }}</h1>

@if($errors->any())
    <p class="mb-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-800">{{ $errors->first() }}</p>
@endif

<form method="post" action="{{ $slide->exists ? route('admin.marketing.slides.update', $slide) : route('admin.marketing.slides.store') }}" enctype="multipart/form-data" class="max-w-xl space-y-4">
    @csrf
    @if($slide->exists)
        @method('PUT')
    @endif

    <div>
        <label class="mb-1 block text-sm text-slate-600">Başlık</label>
        <input name="title" value="{{ old('title', $slide->title) }}" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />
    </div>
    <div>
        <label class="mb-1 block text-sm text-slate-600">Alt başlık</label>
        <input name="subtitle" value="{{ old('subtitle', $slide->subtitle) }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />
    </div>
    <div>
        <label class="mb-1 block text-sm text-slate-600">CTA etiketi</label>
        <input name="cta_label" value="{{ old('cta_label', $slide->cta_label) }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />
    </div>
    <div>
        <label class="mb-1 block text-sm text-slate-600">CTA URL</label>
        <input name="cta_url" value="{{ old('cta_url', $slide->cta_url) }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />
    </div>
    @if($slide->exists)
        <div>
            <label class="mb-1 block text-sm text-slate-600">Sıra</label>
            <input name="sort_order" type="number" value="{{ old('sort_order', $slide->sort_order) }}" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="active" value="1" @checked(old('active', $slide->active)) /> Aktif</label>
    @endif
    <div>
        <label class="mb-1 block text-sm text-slate-600">Görsel (opsiyonel)</label>
        <input type="file" name="image" accept="image/*" class="text-sm" />
        @if($slide->image_path)
            <p class="mt-1 text-xs text-slate-500">Mevcut: {{ $slide->image_path }}</p>
        @endif
    </div>

    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">Kaydet</button>
    <a href="{{ route('admin.marketing.slides.index') }}" class="ml-2 text-sm text-slate-600 hover:underline">İptal</a>
</form>
@endsection
