@extends('layouts.admin')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Tanıtım sitesi (CMS)</h1>
    <p class="mt-1 text-sm text-slate-600">{{ $site->name }} — kök <code class="rounded bg-slate-100 px-1">/</code> adresinde gösterilir.</p>
</div>

@if(session('ok'))
    <p class="mb-4 rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('ok') }}</p>
@endif
@if(session('error'))
    <p class="mb-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-800">{{ session('error') }}</p>
@endif

<ul class="grid gap-3 sm:grid-cols-2">
    <li><a class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-amber-400" href="{{ route('admin.marketing.home.edit') }}">Ana sayfa blokları</a></li>
    <li><a class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-amber-400" href="{{ route('admin.marketing.menu.index') }}">Üst menü</a></li>
    <li><a class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-amber-400" href="{{ route('admin.marketing.slides.index') }}">Slider</a></li>
    <li><a class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-amber-400" href="{{ route('admin.marketing.footer.edit') }}">Footer</a></li>
    <li><a class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-amber-400" href="{{ route('admin.marketing.leads.index') }}">İletişim talepleri</a></li>
    <li><a class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-amber-400" href="{{ route('admin.marketing.phase2') }}">Faz 2 — yol haritası</a></li>
    <li><a class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-amber-400" href="{{ route('marketing.home') }}" target="_blank" rel="noopener">Canlı siteyi aç</a></li>
</ul>
@endsection
