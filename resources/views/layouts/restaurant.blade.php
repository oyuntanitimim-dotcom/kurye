@extends('layouts.app')

@section('body')
    @if(request()->boolean('embed'))
        <div class="min-h-screen bg-slate-50">
            <div class="mx-auto max-w-5xl px-4 py-5">
                @yield('content')
            </div>
        </div>
    @else
        <x-panel.shell brand="İşletme">
            <x-slot:sidebar>
                @include('components.panel.nav-restaurant')
            </x-slot:sidebar>
            <x-slot:header>
                @auth
                    <div class="flex min-w-0 flex-1 items-center justify-end gap-2 sm:gap-3">
                        @include('partials.restaurant-header-bell')
                        <span class="truncate text-sm text-slate-600">{{ auth()->user()->name }}</span>
                    </div>
                @endauth
            </x-slot:header>

            @yield('content')
        </x-panel.shell>
        @include('partials.restaurant-external-order-alert')
    @endif

    <dialog id="restaurant-global-modal" class="w-[min(1100px,95vw)] rounded-2xl border border-slate-200 p-0 shadow-2xl backdrop:bg-black/40">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-white px-5 py-3">
            <div class="min-w-0">
                <h2 class="truncate text-lg font-semibold text-slate-900" id="restaurant-global-modal-title">Detay</h2>
                <p class="mt-0.5 text-sm text-slate-600" id="restaurant-global-modal-subtitle">Popup içinde göster</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" id="restaurant-global-modal-print" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 hover:bg-slate-50">Yazdır</button>
                <button type="button" id="restaurant-global-modal-close" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 hover:bg-slate-50">Kapat</button>
            </div>
        </div>
        <div class="h-[80vh] bg-white">
            <iframe id="restaurant-global-modal-iframe" src="about:blank" class="h-full w-full" referrerpolicy="no-referrer"></iframe>
        </div>
    </dialog>

    @push('scripts')
    <script>
    (function () {
        var modal = document.getElementById('restaurant-global-modal');
        var iframe = document.getElementById('restaurant-global-modal-iframe');
        var closeBtn = document.getElementById('restaurant-global-modal-close');
        var printBtn = document.getElementById('restaurant-global-modal-print');
        var titleEl = document.getElementById('restaurant-global-modal-title');
        var subEl = document.getElementById('restaurant-global-modal-subtitle');
        if (!modal || !iframe) return;

        function withEmbed(url) {
            try {
                var u = new URL(url, window.location.origin);
                if (!u.searchParams.has('embed')) u.searchParams.set('embed', '1');
                return u.toString();
            } catch (e) {
                // Fallback for relative urls
                if (url.indexOf('?') === -1) return url + '?embed=1';
                if (url.indexOf('embed=') !== -1) return url;
                return url + '&embed=1';
            }
        }

        function openWithUrl(url, title, subtitle) {
            iframe.src = withEmbed(url);
            if (titleEl && title) titleEl.textContent = title;
            if (subEl && subtitle) subEl.textContent = subtitle;
            if (typeof modal.showModal === 'function') modal.showModal();
            else modal.setAttribute('open', 'open');
        }

        function close() {
            if (typeof modal.close === 'function') modal.close();
            else modal.removeAttribute('open');
            iframe.src = 'about:blank';
        }

        document.addEventListener('click', function (e) {
            var a = e.target && e.target.closest ? e.target.closest('a[data-open-modal]') : null;
            if (!a) return;
            var url = a.getAttribute('href');
            if (!url) return;
            e.preventDefault();
            openWithUrl(url, a.getAttribute('data-modal-title') || 'Detay', a.getAttribute('data-modal-subtitle') || 'Popup içinde göster');
        });

        if (closeBtn) closeBtn.addEventListener('click', close);
        if (printBtn) printBtn.addEventListener('click', function () {
            try {
                if (iframe.contentWindow) iframe.contentWindow.print();
            } catch (e) {
                // ignore
            }
        });
        modal.addEventListener('click', function (e) {
            if (e.target === modal) close();
        });
    })();
    </script>
    @endpush
@endsection
