@php
    $active = 'block rounded-md border-l-[3px] border-panel-accent bg-panel-accent-soft py-2 pl-2.5 pr-2 text-sm font-medium text-slate-900';
    $idle = 'block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100';
    $workOpen = request()->routeIs('courier.dashboard') || request()->routeIs('courier.deliveries.*');
@endphp

<div class="space-y-1">
    <p class="px-2 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">Ana menü</p>
</div>

<details class="group mt-1" @if($workOpen) open @endif>
    <summary class="flex min-h-11 touch-manipulation cursor-pointer list-none items-center justify-between rounded-md px-2 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden hover:bg-slate-50">
        <span>İşler</span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </summary>
    <div class="ml-2 mt-0.5 space-y-0.5 border-l-2 border-slate-100 pl-2 pb-1">
        <a class="{{ request()->routeIs('courier.dashboard') ? $active : $idle }}" href="{{ route('courier.dashboard') }}">Aktif işler</a>
        <a class="{{ request()->routeIs('courier.deliveries.*') ? $active : $idle }}" href="{{ route('courier.deliveries.index') }}">Teslimatlar</a>
    </div>
</details>

<div class="mt-3 space-y-1">
    <a class="{{ request()->routeIs('courier.earnings') ? $active : $idle }}" href="{{ route('courier.earnings') }}">Kazanç</a>
</div>
