@php
    $link = fn (string $route) => request()->routeIs($route) ? 'rounded-md border border-slate-200 bg-slate-900 px-3 py-1.5 text-sm font-medium text-white' : 'rounded-md border border-transparent px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-100';
@endphp
<nav class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-4" aria-label="Finans alt menü">
    <a class="{{ $link('admin.finance.index') }}" href="{{ route('admin.finance.index', request()->only(['date_from', 'date_to'])) }}">Genel durum</a>
    <a class="{{ $link('admin.finance.firms') }}" href="{{ route('admin.finance.firms', request()->only(['date_from', 'date_to'])) }}">Şirket ciroları</a>
    <a class="{{ $link('admin.finance.reconciliation') }}" href="{{ route('admin.finance.reconciliation') }}">Mutabakat</a>
</nav>
