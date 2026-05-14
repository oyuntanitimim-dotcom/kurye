<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin' }}</title>
    @include('partials.theme-fonts')
    <style>
        @include('partials.theme-css-unified')
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="sidebar__brand">
            <span class="sidebar__badge">Admin</span>
            <h3>Yönetim</h3>
        </div>
        <nav class="sidebar__nav" aria-label="Ana menü">
            <ul class="menu-root">
                <li><a class="single-link nav-link" href="{{ route('admin.dashboard') }}">Gösterge</a></li>
                <li>
                    <details class="menu-group" open>
                        <summary><span class="arrow">▶</span><span>İşletmeler</span></summary>
                        <ul class="submenu">
                            <li><a class="nav-link" href="{{ route('admin.firms.index') }}">Kurye şirketleri</a></li>
                            <li><a class="nav-link" href="{{ route('admin.firms.create') }}">Yeni kurye şirketi</a></li>
                            <li><a class="nav-link" href="{{ route('admin.restaurants.index') }}">Restoranlar</a></li>
                            <li><a class="nav-link" href="{{ route('admin.couriers.index') }}">Kuryeler</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details class="menu-group" open>
                        <summary><span class="arrow">▶</span><span>Operasyon</span></summary>
                        <ul class="submenu">
                            <li><a class="nav-link" href="{{ route('admin.users.index') }}">Kullanıcılar</a></li>
                            <li><a class="nav-link" href="{{ route('admin.orders.index') }}">Siparişler — tümü</a></li>
                            <li><a class="nav-link" href="{{ route('admin.orders.index', ['active' => 1]) }}">Siparişler — aktif</a></li>
                            <li><a class="nav-link" href="{{ route('admin.orders.index', ['awaiting_courier' => 1]) }}">Kurye bekleyen (hazır)</a></li>
                            <li><a class="nav-link" href="{{ route('admin.orders.index', ['status' => 'delivered']) }}">Tamamlanan</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details class="menu-group">
                        <summary><span class="arrow">▶</span><span>Pazarlama</span></summary>
                        <ul class="submenu">
                            <li><a class="nav-link" href="{{ route('admin.campaigns.index') }}">Kampanyalar</a></li>
                            <li><a class="nav-link" href="{{ route('admin.coupons.index') }}">Kuponlar</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details class="menu-group">
                        <summary><span class="arrow">▶</span><span>Finans</span></summary>
                        <ul class="submenu">
                            <li><a class="nav-link" href="{{ route('admin.finance.index') }}">Genel durum</a></li>
                            <li><a class="nav-link" href="{{ route('admin.finance.firms') }}">Şirket ciroları</a></li>
                            <li><a class="nav-link" href="{{ route('admin.finance.reconciliation') }}">Mutabakat</a></li>
                        </ul>
                    </details>
                </li>
                <li><a class="single-link nav-link" href="{{ route('admin.reports.index') }}">Raporlar</a></li>
                <li><a class="single-link nav-link" href="{{ route('notifications.index') }}">Bildirimler</a></li>
                <li><a class="single-link nav-link" href="{{ route('admin.settings') }}">Ayarlar</a></li>
            </ul>
        </nav>
        <div class="sidebar__footer">
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn">Çıkış</button>
            </form>
        </div>
    </aside>
    <main class="content">
        @if(session('ok'))
            <p class="alert-ok">{{ session('ok') }}</p>
        @endif
        @if(session('error'))
            <p class="alert-err">{{ session('error') }}</p>
        @endif
        <div class="content-inner-card">
            @yield('content')
        </div>
    </main>
</div>
<script>
    (function () {
        const contentEl = document.querySelector('.content');
        if (!contentEl) return;

        const loadIntoContent = async (url, push = true) => {
            contentEl.classList.add('content-loading');
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await res.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const nextContent = doc.querySelector('.content');
                if (!nextContent) {
                    window.location.href = url;
                    return;
                }

                contentEl.innerHTML = nextContent.innerHTML;
                document.title = doc.title || document.title;
                if (push) history.pushState({ url }, '', url);
            } catch (e) {
                window.location.href = url;
            } finally {
                contentEl.classList.remove('content-loading');
            }
        };

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a.nav-link');
            if (!link) return;
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            if (link.target && link.target !== '_self') return;

            event.preventDefault();
            loadIntoContent(link.href, true);
        });

        window.addEventListener('popstate', () => {
            loadIntoContent(window.location.href, false);
        });
    })();
</script>
</body>
</html>
