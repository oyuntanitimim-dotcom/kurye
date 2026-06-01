@php
    $active = 'block rounded-md border-l-[3px] border-panel-accent bg-panel-accent-soft py-2 pl-2.5 pr-2 text-sm font-medium text-slate-900';
    $idle = 'block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100';
    $tenantOpen = request()->routeIs('admin.firms.*') || request()->routeIs('admin.restaurants.*') || request()->routeIs('admin.couriers.*');
    $opsOpen = request()->routeIs('admin.users.*') || request()->routeIs('admin.orders.*');
    $mktOpen = request()->routeIs('admin.campaigns.*') || request()->routeIs('admin.coupons.*');
    $siteCmsOpen = request()->routeIs('admin.marketing.*');
    $financeOpen = request()->routeIs('admin.finance.*');
    $creditsOpen = request()->routeIs('admin.credits.*');
    $ordersIdx = request()->routeIs('admin.orders.index');
    $ordersPresetAll = $ordersIdx && ! request()->boolean('active') && ! request()->boolean('awaiting_courier') && ! request()->filled('status');
    $ordersPresetActive = $ordersIdx && request()->boolean('active') && ! request()->boolean('awaiting_courier');
    $ordersPresetAwait = $ordersIdx && request()->boolean('awaiting_courier');
    $ordersPresetDone = $ordersIdx && request()->input('status') === 'delivered';
@endphp

<div class="space-y-1">
    <p class="px-2 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">Ana menü</p>
    <a class="{{ request()->routeIs('admin.dashboard') ? $active : $idle }}" href="{{ route('admin.dashboard') }}">Gösterge</a>
</div>

<details class="group mt-3" @if($tenantOpen) open @endif>
    <summary class="flex min-h-11 touch-manipulation cursor-pointer list-none items-center justify-between rounded-md px-2 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden hover:bg-slate-50">
        <span>İşletmeler</span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </summary>
    <div class="ml-2 mt-0.5 space-y-0.5 border-l-2 border-slate-100 pl-2 pb-1">
        <a class="{{ request()->routeIs('admin.firms.*') && ! request()->routeIs('admin.firms.create') ? $active : $idle }}" href="{{ route('admin.firms.index') }}">Kurye şirketleri</a>
        <a class="{{ request()->routeIs('admin.restaurants.*') ? $active : $idle }}" href="{{ route('admin.restaurants.index') }}">Restoranlar</a>
        <a class="{{ request()->routeIs('admin.couriers.*') ? $active : $idle }}" href="{{ route('admin.couriers.index') }}">Kuryeler</a>
    </div>
</details>

<details class="group mt-3" @if($opsOpen) open @endif>
    <summary class="flex min-h-11 touch-manipulation cursor-pointer list-none items-center justify-between rounded-md px-2 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden hover:bg-slate-50">
        <span>Operasyon</span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </summary>
    <div class="ml-2 mt-0.5 space-y-0.5 border-l-2 border-slate-100 pl-2 pb-1">
        <a class="{{ request()->routeIs('admin.users.*') ? $active : $idle }}" href="{{ route('admin.users.index') }}">Kullanıcılar</a>
        <a class="{{ $ordersPresetAll ? $active : $idle }}" href="{{ route('admin.orders.index') }}">Siparişler — tümü</a>
        <a class="{{ $ordersPresetActive ? $active : $idle }}" href="{{ route('admin.orders.index', ['active' => 1]) }}">Siparişler — aktif</a>
        <a class="{{ $ordersPresetAwait ? $active : $idle }}" href="{{ route('admin.orders.index', ['awaiting_courier' => 1]) }}">Kurye bekleyen (hazır)</a>
        <a class="{{ $ordersPresetDone ? $active : $idle }}" href="{{ route('admin.orders.index', ['status' => 'delivered']) }}">Tamamlanan</a>
    </div>
</details>

<details class="group mt-3" @if($mktOpen) open @endif>
    <summary class="flex min-h-11 touch-manipulation cursor-pointer list-none items-center justify-between rounded-md px-2 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden hover:bg-slate-50">
        <span>Pazarlama</span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </summary>
    <div class="ml-2 mt-0.5 space-y-0.5 border-l-2 border-slate-100 pl-2 pb-1">
        <a class="{{ request()->routeIs('admin.campaigns.*') ? $active : $idle }}" href="{{ route('admin.campaigns.index') }}">Kampanyalar</a>
        <a class="{{ request()->routeIs('admin.coupons.*') ? $active : $idle }}" href="{{ route('admin.coupons.index') }}">Kuponlar</a>
    </div>
</details>

<details class="group mt-3" @if($financeOpen) open @endif>
    <summary class="flex min-h-11 touch-manipulation cursor-pointer list-none items-center justify-between rounded-md px-2 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden hover:bg-slate-50">
        <span>Finans</span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </summary>
    <div class="ml-2 mt-0.5 space-y-0.5 border-l-2 border-slate-100 pl-2 pb-1">
        <a class="{{ request()->routeIs('admin.finance.index') ? $active : $idle }}" href="{{ route('admin.finance.index') }}">Genel durum</a>
        <a class="{{ request()->routeIs('admin.finance.firms') ? $active : $idle }}" href="{{ route('admin.finance.firms') }}">Şirket ciroları</a>
        <a class="{{ request()->routeIs('admin.finance.reconciliation') ? $active : $idle }}" href="{{ route('admin.finance.reconciliation') }}">Mutabakat</a>
    </div>
</details>

<details class="group mt-3" @if($creditsOpen) open @endif>
    <summary class="flex min-h-11 touch-manipulation cursor-pointer list-none items-center justify-between rounded-md px-2 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden hover:bg-slate-50">
        <span>Kontör</span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </summary>
    <div class="ml-2 mt-0.5 space-y-0.5 border-l-2 border-slate-100 pl-2 pb-1">
        <a class="{{ request()->routeIs('admin.credits.firms') ? $active : $idle }}" href="{{ route('admin.credits.firms') }}">Firma kontörleri</a>
        <a class="{{ request()->routeIs('admin.credits.purchases') ? $active : $idle }}" href="{{ route('admin.credits.purchases') }}">Satın alma talepleri</a>
        <a class="{{ request()->routeIs('admin.credits.settings') ? $active : $idle }}" href="{{ route('admin.credits.settings') }}">Fiyat / ayarlar</a>
    </div>
</details>

<div class="mt-3 space-y-1">
    <p class="px-2 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">Rapor</p>
    <a class="{{ request()->routeIs('admin.reports.*') ? $active : $idle }}" href="{{ route('admin.reports.index') }}">Raporlar</a>
</div>

<details class="group mt-3" @if($siteCmsOpen) open @endif>
    <summary class="flex min-h-11 touch-manipulation cursor-pointer list-none items-center justify-between rounded-md px-2 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden hover:bg-slate-50">
        <span>Sayfa yönetimi</span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </summary>
    <div class="ml-2 mt-0.5 space-y-0.5 border-l-2 border-slate-100 pl-2 pb-1">
        <a class="{{ request()->routeIs('admin.marketing.index') ? $active : $idle }}" href="{{ route('admin.marketing.index') }}">Tanıtım CMS</a>
        <a class="{{ request()->routeIs('admin.marketing.home.*') ? $active : $idle }}" href="{{ route('admin.marketing.home.edit') }}">Ana sayfa</a>
        <a class="{{ request()->routeIs('admin.marketing.menu.*') ? $active : $idle }}" href="{{ route('admin.marketing.menu.index') }}">Üst menü</a>
        <a class="{{ request()->routeIs('admin.marketing.slides.*') ? $active : $idle }}" href="{{ route('admin.marketing.slides.index') }}">Slider</a>
        <a class="{{ request()->routeIs('admin.marketing.footer.*') ? $active : $idle }}" href="{{ route('admin.marketing.footer.edit') }}">Footer</a>
        <a class="{{ request()->routeIs('admin.marketing.leads.*') ? $active : $idle }}" href="{{ route('admin.marketing.leads.index') }}">İletişim talepleri</a>
        <a class="{{ request()->routeIs('admin.marketing.phase2') ? $active : $idle }}" href="{{ route('admin.marketing.phase2') }}">Faz 2</a>
    </div>
</details>

<div class="mt-3 space-y-1">
    <p class="px-2 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">Sistem</p>
    <a class="{{ request()->routeIs('notifications.*') ? $active : $idle }}" href="{{ route('notifications.index') }}">Bildirimler</a>
    <a class="{{ request()->routeIs('admin.settings') ? $active : $idle }}" href="{{ route('admin.settings') }}">Ayarlar</a>
</div>
