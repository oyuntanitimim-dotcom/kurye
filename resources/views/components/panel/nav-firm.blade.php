@php
    use App\Enums\OrderStatus;

    $active = 'block rounded-md border-l-[3px] border-panel-accent bg-panel-accent-soft py-2 pl-2.5 pr-2 text-sm font-medium text-slate-900';
    $idle = 'block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100';
    $ordersBase = request()->routeIs('firm.orders.*');
    $ordersAll = $ordersBase && ! request()->filled('status');
    $ordersDelivered = $ordersBase && request('status') === OrderStatus::Delivered->value;
    $ordersCancelled = $ordersBase && request('status') === OrderStatus::Cancelled->value;
    $financeOpen = request()->routeIs('firm.finance.*');
@endphp

<div class="space-y-1">
    <p class="px-2 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">Ana menü</p>
    <a class="{{ request()->routeIs('firm.dashboard') ? $active : $idle }}" href="{{ route('firm.dashboard') }}">Gösterge</a>
    <a class="{{ request()->routeIs('firm.operations.*') ? $active : $idle }}" href="{{ route('firm.operations.index') }}">Operasyon</a>
</div>

<details class="group mt-3" @if($ordersBase) open @endif>
    <summary class="flex min-h-11 touch-manipulation cursor-pointer list-none items-center justify-between rounded-md px-2 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden hover:bg-slate-50">
        <span>Siparişler</span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </summary>
    <div class="ml-2 mt-0.5 space-y-0.5 border-l-2 border-slate-100 pl-2 pb-1">
        <a class="{{ $ordersAll ? $active : $idle }}" href="{{ route('firm.orders.index') }}">Tümü</a>
        <a class="{{ $ordersDelivered ? $active : $idle }}" href="{{ route('firm.orders.index', ['status' => OrderStatus::Delivered->value]) }}">Teslim edilenler</a>
        <a class="{{ $ordersCancelled ? $active : $idle }}" href="{{ route('firm.orders.index', ['status' => OrderStatus::Cancelled->value]) }}">İptal edilenler</a>
    </div>
</details>

<div class="mt-3 space-y-1">
    <p class="px-2 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">İşletmeler</p>
    <a class="{{ request()->routeIs('firm.restaurants.*') ? $active : $idle }}" href="{{ route('firm.restaurants.index') }}">Restoranlar</a>
    <a class="{{ request()->routeIs('firm.couriers.*') ? $active : $idle }}" href="{{ route('firm.couriers.index') }}">Kuryeler</a>
</div>

<details class="group mt-3" @if(request()->routeIs('firm.campaigns.*') || request()->routeIs('firm.coupons.*')) open @endif>
    <summary class="flex min-h-11 touch-manipulation cursor-pointer list-none items-center justify-between rounded-md px-2 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden hover:bg-slate-50">
        <span>Kampanya</span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </summary>
    <div class="ml-2 mt-0.5 space-y-0.5 border-l-2 border-slate-100 pl-2 pb-1">
        <a class="{{ request()->routeIs('firm.campaigns.*') ? $active : $idle }}" href="{{ route('firm.campaigns.index') }}">Kampanyalar</a>
        <a class="{{ request()->routeIs('firm.coupons.*') ? $active : $idle }}" href="{{ route('firm.coupons.index') }}">Kuponlar</a>
    </div>
</details>

<details class="group mt-3" @if(request()->routeIs('firm.reports.*')) open @endif>
    <summary class="flex min-h-11 touch-manipulation cursor-pointer list-none items-center justify-between rounded-md px-2 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden hover:bg-slate-50">
        <span>Raporlar</span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </summary>
    <div class="ml-2 mt-0.5 space-y-0.5 border-l-2 border-slate-100 pl-2 pb-1">
        <a class="{{ request()->routeIs('firm.reports.*') ? $active : $idle }}" href="{{ route('firm.reports.index') }}">Genel raporlar</a>
    </div>
</details>

<details class="group mt-3" @if($financeOpen) open @endif>
    <summary class="flex min-h-11 touch-manipulation cursor-pointer list-none items-center justify-between gap-2 rounded-md px-2 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden hover:bg-slate-50">
        <span class="flex min-w-0 items-center gap-2">
            <span>Finans</span>
        </span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </summary>
    <div class="ml-2 mt-0.5 space-y-0.5 border-l-2 border-slate-100 pl-2 pb-1">
        <a class="{{ request()->routeIs('firm.finance.overview') ? $active : $idle }}" href="{{ route('firm.finance.overview') }}">Genel durum</a>
        <a class="{{ request()->routeIs('firm.finance.balances') ? $active : $idle }}" href="{{ route('firm.finance.balances') }}">Borç / alacak listesi</a>
        <a class="{{ request()->routeIs('firm.finance.courier_collections') ? $active : $idle }}" href="{{ route('firm.finance.courier_collections') }}">Kurye ücret özeti</a>
        <a class="{{ request()->routeIs('firm.finance.courier_payouts.*') ? $active : $idle }}" href="{{ route('firm.finance.courier_payouts.index') }}">Kurye ödemeleri</a>
        <a class="{{ request()->routeIs('firm.finance.reconciliation') ? $active : $idle }}" href="{{ route('firm.finance.reconciliation') }}">Mutabakat</a>
    </div>
</details>

<div class="mt-3 space-y-1">
    <p class="px-2 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">Sistem</p>
    <a class="{{ request()->routeIs('notifications.*') ? $active : $idle }}" href="{{ route('notifications.index') }}">Bildirimler</a>
    <a class="{{ request()->routeIs('firm.settings.*') ? $active : $idle }}" href="{{ route('firm.settings.edit') }}">Ayarlar</a>
</div>
