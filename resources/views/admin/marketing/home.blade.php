@extends('layouts.admin')

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Ana sayfa blokları</h1>
        <p class="mt-1 text-sm text-slate-600">Durum: <strong>{{ $page->status }}</strong>
            @if($page->publishedVersion)
                — yayın sürümü #{{ $page->publishedVersion->version }}
            @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Taslak önizleme</a>
    </div>
</div>

@if(session('ok'))
    <p class="mb-4 rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('ok') }}</p>
@endif
@if(session('error'))
    <p class="mb-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-800">{{ session('error') }}</p>
@endif
@if($errors->any())
    <p class="mb-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-800">{{ $errors->first() }}</p>
@endif

<form method="post" action="{{ route('admin.marketing.home.update') }}" class="space-y-6">
    @csrf
    @method('PUT')

    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700">Blok sırası (sürükleyin) / JSON</label>
        <ol id="block-sort-list" class="mb-3 space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3"></ol>
        <textarea name="blocks_json" id="blocks-json" rows="22" class="w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm">{{ old('blocks_json', $blocksJson) }}</textarea>
    </div>

    <details class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
        <summary class="cursor-pointer font-medium text-slate-900">Blok tipleri (referans)</summary>
        <p class="mt-2 text-xs text-slate-600">JSON dizisinde her eleman <code class="rounded bg-white px-1">type</code> ve <code class="rounded bg-white px-1">data</code> içerir.</p>
        <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-slate-600">
            <li><code>hero_slider</code> — <code>slides[]</code> (badge, headline, headline_accent?, subheadline, cta_*, banner_image?, pillars[]?) ve isteğe bağlı <code>side_banner</code></li>
            <li><code>hero</code> — (eski) tek kart; senkron <code>hero</code> kaldırıp <code>hero_slider</code> ekler</li>
            <li><code>stats</code> — title, items[{value,label}]</li>
            <li><code>feature_strip</code> — <code>items[]</code> ({title, body}); üç modül kartı; anchor <code>#ozellikler</code></li>
            <li><code>integrations</code> — title, subtitle, platforms[{name}]</li>
            <li><code>feature_grid</code> — title, subtitle?, items[{title,body}]; anchor <code>#ozellikler</code></li>
            <li><code>process_steps</code> — title, subtitle?, steps[] veya items[] ({number?, title, body}); anchor <code>#nasil-calisir</code></li>
            <li><code>screens</code> — title, subtitle, items[{title,caption,image?}]</li>
            <li><code>testimonials</code> — title, items[{quote,author,role?,company?}]</li>
            <li><code>pricing</code> — title, subtitle, plans[{name,price,period,description,features[],cta_label,cta_url,highlight}]</li>
            <li><code>contact_form</code> — title, subtitle → POST <code>/iletisim</code></li>
            <li><code>cta</code> — title, body?, cta_label, cta_url, cta_secondary_label?, cta_secondary_url?</li>
            <li><code>faq</code>, <code>logo_cloud</code>, <code>rich_text</code>, <code>legal_notices</code></li>
        </ul>
    </details>

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Taslak kaydet</button>
    </div>
</form>

<form method="post" action="{{ route('admin.marketing.home.publish') }}" class="mt-4" onsubmit="return confirm('Yayınlanan sürüm ziyaretçilere gösterilecek. Devam?');">
    @csrf
    <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">Yayınla</button>
</form>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const textarea = document.getElementById('blocks-json');
    const list = document.getElementById('block-sort-list');
    if (!textarea || !list) return;

    function parseBlocks() {
        try { return JSON.parse(textarea.value); } catch (e) { return []; }
    }

    function renderList(blocks) {
        list.innerHTML = '';
        blocks.forEach((block, idx) => {
            const li = document.createElement('li');
            li.className = 'cursor-grab rounded-md border border-slate-200 bg-white px-3 py-2 text-sm active:cursor-grabbing';
            li.dataset.idx = String(idx);
            const type = block && block.type ? block.type : '?';
            li.textContent = type + ' — blok #' + (idx + 1);
            list.appendChild(li);
        });
    }

    function syncOrderFromDom() {
        const blocks = parseBlocks();
        const order = [...list.querySelectorAll('li')].map(li => Number(li.dataset.idx));
        if (!order.length || order.some(n => Number.isNaN(n))) return;
        const reordered = order.map(i => blocks[i]);
        textarea.value = JSON.stringify(reordered, null, 2);
        renderList(reordered);
    }

    renderList(parseBlocks());
    textarea.addEventListener('change', () => renderList(parseBlocks()));

    if (window.Sortable) {
        Sortable.create(list, {
            animation: 150,
            onEnd: syncOrderFromDom,
        });
    }
})();
</script>
@endpush
@endsection
