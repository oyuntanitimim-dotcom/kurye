@props([
    'brand' => 'Panel',
])

@php
    $notifyCount = auth()->check()
        ? auth()->user()->inAppNotifications()->where('status', 'unread')->count()
        : 0;
@endphp

<div id="panel-shell" class="flex min-h-screen bg-panel-canvas text-slate-900 antialiased">
    {{-- Mobil: menü açıkken arka plan --}}
    <div
        id="panel-nav-backdrop"
        class="fixed inset-0 z-30 hidden bg-slate-900/50 md:hidden"
        aria-hidden="true"
    ></div>

    {{--
      Masaüstü (md+): aside normal flex çocuğu — içerik yanında kalır.
      Mobil: yalnızca max-md: fixed + data-panel-open ile transform (JS sınıf eklemez).
    --}}
    <aside
        id="panel-sidebar"
        data-panel-open="false"
        class="flex w-64 shrink-0 flex-col border-r border-slate-200 bg-panel-sidebar shadow-sm max-md:fixed max-md:inset-y-0 max-md:left-0 max-md:z-40 max-md:max-w-[min(18rem,calc(100vw-2.5rem))] max-md:shadow-xl max-md:transition-transform max-md:duration-200 max-md:ease-out max-md:motion-reduce:transition-none max-md:data-[panel-open=false]:-translate-x-full max-md:data-[panel-open=true]:translate-x-0 md:relative md:z-0 md:translate-x-0"
        role="navigation"
        aria-label="Ana menü"
    >
        <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-4">
            <div class="min-w-0 truncate text-lg font-semibold tracking-tight text-slate-900">{{ $brand }}</div>
            <button
                type="button"
                id="panel-nav-close"
                class="inline-flex shrink-0 touch-manipulation items-center justify-center rounded-md p-2.5 text-slate-600 hover:bg-slate-100 md:hidden"
                aria-label="Menüyü kapat"
            >
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-2 py-3">
            {{ $sidebar }}
        </div>
        <div class="space-y-1 border-t border-slate-100 p-3">
            @auth
                <a href="{{ route('notifications.index') }}"
                    class="flex touch-manipulation items-center justify-between rounded-md px-3 py-2.5 text-sm {{ request()->routeIs('notifications.*') ? 'border-l-[3px] border-panel-accent bg-panel-accent-soft font-medium text-slate-900' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Bildirimler</span>
                    @if($notifyCount > 0)
                        <span class="rounded-full bg-panel-accent px-1.5 text-xs font-medium text-white">{{ $notifyCount }}</span>
                    @endif
                </a>
                <form method="post" action="{{ route('logout') }}" class="pt-1">
                    @csrf
                    <button type="submit" class="w-full touch-manipulation rounded-md px-3 py-2.5 text-left text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                        Çıkış
                    </button>
                </form>
            @endauth
        </div>
    </aside>
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-14 shrink-0 items-center gap-2 border-b border-slate-200 bg-white px-3 sm:px-4 md:px-6">
            <button
                type="button"
                id="panel-nav-open"
                class="inline-flex touch-manipulation items-center justify-center rounded-md p-2.5 text-slate-600 hover:bg-slate-100 md:hidden"
                aria-expanded="false"
                aria-controls="panel-sidebar"
                aria-label="Menüyü aç"
            >
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
            <div class="min-w-0 flex-1 truncate text-base font-semibold text-slate-900 md:hidden">{{ $brand }}</div>
            <div class="flex shrink-0 items-center justify-end gap-2 md:min-w-0 md:flex-1 md:justify-end">
                @isset($header)
                    {{ $header }}
                @endisset
            </div>
        </header>
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            @if (session('ok'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-900">{{ session('ok') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800">{{ session('error') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const shell = document.getElementById('panel-shell');
    const sidebar = document.getElementById('panel-sidebar');
    const backdrop = document.getElementById('panel-nav-backdrop');
    const openBtn = document.getElementById('panel-nav-open');
    const closeBtn = document.getElementById('panel-nav-close');
    if (!shell || !sidebar || !openBtn) return;

    const mq = window.matchMedia('(min-width: 768px)');

    function isDesktop() {
        return mq.matches;
    }

    function setOpen(open) {
        sidebar.dataset.panelOpen = open ? 'true' : 'false';
        openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open && !isDesktop()) {
            backdrop?.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        } else {
            backdrop?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }

    function openNav() {
        if (isDesktop()) return;
        setOpen(true);
    }

    function closeNav() {
        setOpen(false);
    }

    openBtn.addEventListener('click', function () {
        if (isDesktop()) return;
        setOpen(sidebar.dataset.panelOpen !== 'true');
    });
    closeBtn?.addEventListener('click', closeNav);
    backdrop?.addEventListener('click', closeNav);

    mq.addEventListener('change', function (e) {
        if (e.matches) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeNav();
        }
    });

    sidebar.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () {
            if (!isDesktop()) {
                closeNav();
            }
        });
    });
})();
</script>
@endpush
