<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Şirket Yönetimi</title>
    @include('partials.theme-fonts')
    <style>
        @include('partials.theme-css-unified')
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="sidebar__brand">
            <span class="sidebar__badge">Şirket</span>
            <h3>{{ $company?->company_name ?? 'Şirket Paneli' }}</h3>
        </div>
        <nav class="sidebar__nav" aria-label="Ana menü">
            <ul class="menu-root">
                <li><a class="single-link nav-link" href="{{ route('company.dashboard') }}">Güncel Durum</a></li>
                <li>
                    <details>
                        <summary><span class="arrow">▶</span><span>Siparişler</span></summary>
                        <ul class="submenu">
                            <li><a class="nav-link" href="{{ route('company.page', 'siparisler-teslim-edilenler') }}">Teslim Edilenler</a></li>
                            <li><a class="nav-link" href="{{ route('company.page', 'siparisler-iptal-edilenler') }}">İptal Edilenler</a></li>
                        </ul>
                    </details>
                </li>
                <li><a class="single-link nav-link" href="{{ route('company.page', 'isletmeler') }}">İşletmeler</a></li>
                <li><a class="single-link nav-link" href="{{ route('company.page', 'kuryeler') }}">Kuryeler</a></li>
                <li class="menu-divider" role="presentation"></li>
                <li>
                    <details>
                        <summary><span class="arrow">▶</span><span>Başvurular</span></summary>
                        <ul class="submenu">
                            <li><a class="nav-link" href="{{ route('company.page', 'basvurular-restoran') }}">Firma</a></li>
                            <li><a class="nav-link" href="{{ route('company.page', 'basvurular-kurye') }}">Kurye</a></li>
                        </ul>
                    </details>
                </li>
                <li class="menu-divider" role="presentation"></li>
                <li><a class="single-link nav-link" href="{{ route('company.page', 'harita') }}">Harita</a></li>
                <li><a class="single-link nav-link" href="{{ route('company.page', 'cari-hesap') }}">Cari Hesap</a></li>
                <li class="menu-divider" role="presentation"></li>
                <li><a class="single-link nav-link" href="{{ route('company.page', 'raporlar') }}">Raporlar</a></li>
                <li><a class="single-link nav-link" href="{{ route('company.page', 'kurye-raporlari') }}">Kurye Raporları</a></li>
                <li><a class="single-link nav-link" href="{{ route('company.page', 'restoran-raporlari') }}">Firma raporları</a></li>
                <li class="menu-divider" role="presentation"></li>
                <li><a class="single-link nav-link" href="{{ route('company.page', 'yonetim') }}">Yönetim</a></li>
                <li><a class="single-link nav-link" href="{{ route('company.page', 'ayarlar') }}">Ayarlar</a></li>
                <li><a class="single-link nav-link" href="{{ route('company.page', 'kontor-yukle') }}">Kontör Yükle</a></li>
            </ul>
        </nav>
        <div class="sidebar__footer">
            <form method="post" action="{{ route('company.logout') }}">
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
        @if($errors->any())
            <div class="alert-err" role="alert">
                <strong>Kaydedilemedi:</strong>
                <ul style="margin:8px 0 0 18px;padding:0">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="content-inner-card">
        @if($page === 'guncel-durum')
            @php
                $siparisSekmesi = $siparisSekmesi ?? 'tum';
                $tabCounts = $companyOrderTabCounts ?? ['tum' => 0, 'manuel' => 0, 'paket' => 0];
                $uiLabels = $orderStatusUiLabels ?? [];
                $mahalleFromAddr = function (?string $a): string {
                    if ($a === null || $a === '') {
                        return '—';
                    }
                    if (preg_match('/,\s*([^,\/]+?)\s*\/\s*[^\/]+/u', $a, $m)) {
                        return trim($m[1]);
                    }

                    return '—';
                };
                $statusRowClass = function ($s): string {
                    return match ($s) {
                        \App\Enums\OrderStatus::Pending => 'status-pending',
                        \App\Enums\OrderStatus::RestaurantPreparing => 'status-preparing',
                        \App\Enums\OrderStatus::ReadyForCourier => 'status-ready',
                        \App\Enums\OrderStatus::CourierAssigned => 'status-assigned',
                        \App\Enums\OrderStatus::CourierPicked => 'status-picked',
                        \App\Enums\OrderStatus::Delivered => 'status-delivered',
                        \App\Enums\OrderStatus::Cancelled => 'status-cancelled',
                    };
                };
                $vehicleLabels = ['motosiklet' => 'Motosiklet', 'araba' => 'Araba', 'bisiklet' => 'Bisiklet', 'diger' => 'Diğer'];
            @endphp
            <div class="gd-head">
                <div>
                    <h1 style="margin-bottom:4px">Güncel Durum / Siparişler</h1>
                    <p class="gd-head__meta">Son güncelleme: {{ ($panelRenderedAt ?? now())->timezone(config('app.timezone'))->format('d.m.Y H:i:s') }}</p>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
                    <label class="stat-toggle" title="Bu sekmedeki aktif sipariş özeti">
                        <input type="checkbox" id="gd-stats-toggle" role="switch">
                        <span>İstatistikler</span>
                    </label>
                    @if($tenantDbLabel)
                        <button type="button" class="btn btn-success" onclick="document.getElementById('company-manual-order-dialog')?.showModal()">Telefon Siparişi Ekle +</button>
                    @else
                        <button type="button" class="btn btn-success" disabled title="Kiracı veritabanı yok">Telefon Siparişi Ekle +</button>
                    @endif
                </div>
            </div>
            <div class="chips" style="margin-bottom:4px">
                <span class="chip">İstatistikler</span>
                <span class="chip active">Siparişler</span>
                <a class="chip nav-link" href="{{ route('company.page', ['sayfa' => 'harita']) }}" style="text-decoration:none;color:inherit">Harita</a>
            </div>
            <div class="gd-tabs">
                <a class="nav-link @if($siparisSekmesi==='tum') active @endif" href="{{ route('company.page', ['sayfa' => 'guncel-durum', 'siparis_sekmesi' => 'tum']) }}">Tüm Siparişler<span class="gd-count">{{ $tabCounts['tum'] ?? 0 }}</span></a>
                <a class="nav-link @if($siparisSekmesi==='manuel') active @endif" href="{{ route('company.page', ['sayfa' => 'guncel-durum', 'siparis_sekmesi' => 'manuel']) }}">Telefon / Şube<span class="gd-count">{{ $tabCounts['manuel'] ?? 0 }}</span></a>
                <a class="nav-link @if($siparisSekmesi==='paket') active @endif" href="{{ route('company.page', ['sayfa' => 'guncel-durum', 'siparis_sekmesi' => 'paket']) }}">Paket Servis<span class="gd-count">{{ $tabCounts['paket'] ?? 0 }}</span></a>
            </div>
            @if(!empty($companyOrderStats))
            <div id="gd-stats-panel" class="gd-stats-panel" hidden>
                <div class="kpis">
                    <div class="kpi"><span>Aktif sipariş (bu sekme)</span><b>{{ $companyOrderStats['count'] }}</b></div>
                    <div class="kpi"><span>Toplam tutar (₺)</span><b>{{ number_format((float) $companyOrderStats['sum_price'], 2, ',', '.') }}</b></div>
                    <div class="kpi"><span>Müsait kurye</span><b>{{ $companyOrderStats['available_couriers'] }}</b></div>
                </div>
                @if(!empty($companyOrderStats['by_status']))
                <div class="gd-status-chips">
                    @foreach($companyOrderStats['by_status'] as $st => $cnt)
                        <span>{{ ($orderStatusLabels[$st] ?? $st) }}: <strong>{{ $cnt }}</strong></span>
                    @endforeach
                </div>
                @endif
            </div>
            @endif
            <div class="toolbar" style="margin:12px 0;">
                <input class="search" id="company-order-search" type="search" placeholder="Ara" autocomplete="off">
                <button class="btn" type="button" id="gd-columns-btn" onclick="window.openGdColumnsDialog && window.openGdColumnsDialog()">Sütunlar</button>
            </div>
            <div class="tbl-wrap">
            <table class="tbl-orders">
                <thead><tr>
                    <th data-oc="time">Saat</th>
                    <th data-oc="order">Sipariş</th>
                    <th data-oc="business">İşletme</th>
                    <th data-oc="customer">Müşteri</th>
                    <th data-oc="payment">Ödeme</th>
                    <th data-oc="neighborhood">Mahalle</th>
                    <th data-oc="status">Durum</th>
                    <th data-oc="staff">Personel</th>
                </tr></thead>
                <tbody>
                @forelse($orders as $order)
                    @php
                        $isManual = str_starts_with((string) $order->order_number, 'MAN-') || (($order->source ?? null) === 'manual');
                        $channel = $isManual ? 'Telefon / Manuel' : 'Paket servis';
                        $blob = strtolower($order->order_number.' '.$order->customer_name.' '.$order->customer_phone.' '.$order->address);
                    @endphp
                    <tr data-order-search="{{ $blob }}">
                        <td data-oc="time" style="white-space:nowrap;font-size:13px">
                            {{ $order->created_at->format('H:i') }}
                            <div style="color:var(--muted);font-size:11px;margin-top:2px">{{ $order->created_at->locale('tr')->diffForHumans() }}</div>
                        </td>
                        <td data-oc="order">
                            <span style="font-size:12px;font-weight:600">{{ $channel }}</span>
                            <div style="color:var(--muted);font-size:11px;margin-top:2px">{{ $order->order_number }}</div>
                        </td>
                        <td data-oc="business" style="font-size:13px">{{ $order->restaurant?->name ?? '—' }}</td>
                        <td data-oc="customer">
                            <div class="cust-block">
                                <div class="nm">{{ $order->customer_name }}</div>
                                <div class="ph">{{ $order->customer_phone }}</div>
                                <div class="adr">{{ $order->address }}</div>
                            </div>
                        </td>
                        <td data-oc="payment" class="pay-cell">
                            {{ number_format((float) $order->price, 2, ',', '.') }} ₺
                            <div class="pay-sub">{{ $isManual ? 'Kapıda nakit (varsayılan)' : '—' }}</div>
                        </td>
                        <td data-oc="neighborhood" style="font-size:13px">{{ $mahalleFromAddr($order->address) }}</td>
                        <td data-oc="status" class="status-wrap">
                            <form method="post" action="{{ route('company.orders.status', $order->id) }}" style="margin:0">
                                @csrf
                                <input type="hidden" name="return_sekmesi" value="{{ $siparisSekmesi }}">
                                <select class="status-select {{ $statusRowClass($order->status) }}" name="status" onchange="this.form.submit()" title="Durum">
                                    @foreach($uiLabels as $val => $label)
                                        <option value="{{ $val }}" @selected($order->status->value === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <details class="row-actions">
                                <summary>İşlemler</summary>
                                <ul>
                                    <li>
                                        <button type="button" class="linklike" data-open-assign-courier
                                            data-order-id="{{ $order->id }}"
                                            data-order-label="{{ $order->order_number }} · {{ $order->customer_name }}"
                                            onclick="window.companyOpenAssignCourier(this); return false;">Kurye yönlendir</button>
                                    </li>
                                    <li><button type="button" onclick="window.print()">Yazdır</button></li>
                                    <li><button type="button" onclick="alert('ÖKC entegrasyonu henüz bağlı değil.')">ÖKC\'a gönder</button></li>
                                </ul>
                            </details>
                        </td>
                        <td data-oc="staff" style="font-size:13px">{{ $order->courier?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">Bu sekmede sipariş yok veya tüm siparişler tamamlandı / iptal.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
            <dialog id="gd-columns-dialog" class="kurye-dialog">
                <div class="kurye-dialog__head">Sütunlar</div>
                <div class="kurye-dialog__body" style="display:grid;gap:10px">
                    <label style="display:flex;gap:8px;align-items:center;font-size:14px"><input type="checkbox" data-col-pref="time" checked> Saat</label>
                    <label style="display:flex;gap:8px;align-items:center;font-size:14px"><input type="checkbox" data-col-pref="order" checked> Sipariş</label>
                    <label style="display:flex;gap:8px;align-items:center;font-size:14px"><input type="checkbox" data-col-pref="business" checked> İşletme</label>
                    <label style="display:flex;gap:8px;align-items:center;font-size:14px"><input type="checkbox" data-col-pref="customer" checked> Müşteri</label>
                    <label style="display:flex;gap:8px;align-items:center;font-size:14px"><input type="checkbox" data-col-pref="payment" checked> Ödeme</label>
                    <label style="display:flex;gap:8px;align-items:center;font-size:14px"><input type="checkbox" data-col-pref="neighborhood" checked> Mahalle</label>
                    <label style="display:flex;gap:8px;align-items:center;font-size:14px"><input type="checkbox" data-col-pref="status" checked> Durum</label>
                    <label style="display:flex;gap:8px;align-items:center;font-size:14px"><input type="checkbox" data-col-pref="staff" checked> Personel</label>
                </div>
                <div class="kurye-dialog__actions">
                    <button type="button" class="btn" onclick="document.getElementById('gd-columns-dialog')?.close()">İptal</button>
                    <button type="button" class="btn btn-primary" id="gd-columns-save">Kaydet</button>
                </div>
            </dialog>
            <dialog id="assign-courier-dialog" class="kurye-dialog">
                <div class="kurye-dialog__head">Kurye yönlendir — <span id="assign-courier-order-label"></span></div>
                <form id="assign-courier-form" method="post" action="#" class="kurye-dialog__body" data-assign-base="{{ url('/company/siparisler') }}">
                    @csrf
                    <input type="hidden" name="return_sekmesi" value="{{ $siparisSekmesi }}">
                    <p style="margin:0;font-size:13px;color:var(--muted)">Yalnızca <strong>müsait</strong> ve <strong>aktif</strong> kuryeler listelenir.</p>
                    <div class="assign-courier-list">
                        @forelse(($couriersForAssign ?? collect()) as $ac)
                            <label class="assign-courier-item">
                                <input type="radio" name="courier_id" value="{{ $ac->id }}" required>
                                <span>
                                    <span class="c-name">{{ $ac->name }}</span>
                                    <span class="c-meta">{{ $ac->phone }} · {{ $vehicleLabels[$ac->vehicle_type] ?? $ac->vehicle_type }}</span>
                                </span>
                            </label>
                        @empty
                            <p style="margin:0;font-size:14px;color:var(--muted)">Müsait kurye yok. <a class="nav-link" href="{{ route('company.page', 'kuryeler') }}">Kuryeler</a> sayfasından en az bir kuryenin durumunu <strong>Müsait</strong> ve hesabını <strong>Aktif</strong> yapın.</p>
                        @endforelse
                    </div>
                    <div class="kurye-dialog__actions">
                        <button type="button" class="btn" onclick="document.getElementById('assign-courier-dialog')?.close()">İptal</button>
                        <button type="submit" class="btn btn-primary" @disabled(($couriersForAssign ?? collect())->isEmpty())>Seçilen kuryeyi ata</button>
                    </div>
                </form>
            </dialog>
            <dialog id="company-manual-order-dialog" class="kurye-dialog" @if(!$tenantDbLabel) hidden @endif>
                <div class="kurye-dialog__head">Telefon / şube siparişi</div>
                <form method="post" action="{{ route('company.orders.manual.store') }}" class="kurye-dialog__body">
                    @csrf
                    <input type="hidden" name="return_sekmesi" value="{{ $siparisSekmesi }}">
                    <div class="kurye-dialog__row">
                        <label for="co-restaurant">İşletme</label>
                        <select id="co-restaurant" name="restaurant_id" required @disabled(!$tenantDbLabel)>
                            <option value="">Seçin</option>
                            @foreach(($companyRestaurants ?? collect()) as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="kurye-dialog__row">
                        <label for="co-customer">Kayıtlı müşteri (opsiyonel)</label>
                        <select id="co-customer" name="customer_id" @disabled(!$tenantDbLabel)>
                            <option value="">Yeni müşteri</option>
                            @foreach(($companyCustomersForManual ?? collect()) as $c)
                                <option value="{{ $c->id }}" data-restaurant="{{ $c->restaurant_id }}">{{ $c->name }} — {{ $c->phone_1 }} ({{ $c->restaurant_name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="kurye-dialog__row co-new-cust">
                        <label for="co-name">Ad Soyad</label>
                        <input id="co-name" name="customer_name" autocomplete="name">
                    </div>
                    <div class="kurye-dialog__row co-new-cust">
                        <label for="co-phone">Telefon</label>
                        <input id="co-phone" name="customer_phone" autocomplete="tel">
                    </div>
                    <div class="kurye-dialog__row">
                        <label for="co-addr-title">Adres başlığı</label>
                        <input id="co-addr-title" name="addr_title" placeholder="Ev, iş...">
                    </div>
                    <div class="kurye-dialog__row">
                        <label for="co-line">Adres satırı</label>
                        <input id="co-line" name="addr_line" required>
                    </div>
                    <div class="kurye-dialog__row">
                        <label for="co-district">İlçe</label>
                        <input id="co-district" name="addr_district" required>
                    </div>
                    <div class="kurye-dialog__row">
                        <label for="co-city">İl</label>
                        <input id="co-city" name="addr_city" required>
                    </div>
                    <div class="kurye-dialog__row">
                        <label for="co-dir">Tarif (opsiyonel)</label>
                        <input id="co-dir" name="addr_directions">
                    </div>
                    <div class="kurye-dialog__row">
                        <label for="co-price">Tutar (₺)</label>
                        <input id="co-price" name="price" type="number" step="0.01" min="0.01" required>
                    </div>
                    <div class="kurye-dialog__actions">
                        <button type="button" class="btn" onclick="document.getElementById('company-manual-order-dialog')?.close()">İptal</button>
                        <button type="submit" class="btn btn-primary" @disabled(!$tenantDbLabel)>Kaydet</button>
                    </div>
                </form>
            </dialog>
        @elseif(in_array($page, ['siparisler-teslim-edilenler', 'siparisler-iptal-edilenler'], true))
            <h1>{{ $page === 'siparisler-teslim-edilenler' ? 'Teslim Edilen Siparişler' : 'İptal Edilen Siparişler' }}</h1>
            <div class="toolbar">
                <input type="date"><input type="date">
                <select><option>Tüm İşletmeler</option></select>
                <select><option>Tüm Sipariş Kanalları</option></select>
                <select><option>Tüm Personeller</option></select>
                <select><option>Ödeme Yöntemi</option></select>
            </div>
            @if($orderKpis)
            <div class="kpis" style="margin:12px 0;">
                <div class="kpi"><span>Paket Sayısı</span><b>{{ $orderKpis['count'] }}</b></div>
                <div class="kpi"><span>Paket Ortalama Tutarı</span><b>{{ $orderKpis['count'] > 0 ? number_format((float) $orderKpis['avg_price'], 2, ',', '.') : '—' }}</b></div>
                <div class="kpi"><span>Toplam Tutar</span><b>{{ number_format((float) ($orderKpis['sum_price'] ?? 0), 2, ',', '.') }}</b></div>
            </div>
            @endif
            <div class="toolbar" style="margin:12px 0;"><input class="search" placeholder="Ara..."><button class="btn" type="button">Sütunlar</button></div>
            <table>
                <thead><tr><th>No</th><th>Müşteri</th><th>Durum</th><th>Tutar</th></tr></thead>
                <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->customer_name }}</td>
                        <td>{{ $orderStatusLabels[$order->status->value] ?? $order->status->value }}</td>
                        <td>{{ number_format((float) $order->price, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Kayıt yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        @elseif($page === 'isletmeler')
            <h1>İşletmeler</h1>
            @if($tenantDbLabel)
                <p class="info-banner page-block">
                    Bu ekranda eklenen veya güncellenen tüm işletme kayıtları <strong>yalnızca bu şirketin</strong> kiracı veritabanına yazılır:
                    <code>{{ $tenantDbLabel }}</code>
                    (merkez platform veritabanına kayıt düşmez.)
                </p>
            @else
                <p class="alert-err" style="margin-bottom:12px">Kiracı veritabanı atanmadığı için işletme kaydı oluşturulamaz veya güncellenemez.</p>
            @endif
            <form method="post" action="{{ route('company.businesses.store') }}" style="margin:12px 0;border:1px solid var(--line);border-radius:10px;padding:12px;background:#fff">
                @csrf
                <p style="margin:0 0 10px;color:#64748b;font-size:12px">İşletme girişi için adres: <strong>{{ url('/isletme/giris') }}</strong></p>
                <div class="toolbar">
                    <input name="name" placeholder="İşletme adı" required @disabled(!$tenantDbLabel)>
                    <input name="phone" placeholder="Telefon" required @disabled(!$tenantDbLabel)>
                    <input name="address" placeholder="Adres" required style="min-width:260px" @disabled(!$tenantDbLabel)>
                    <input type="password" name="login_password" placeholder="İşletme şifresi" required @disabled(!$tenantDbLabel)>
                    <select name="status" @disabled(!$tenantDbLabel)><option value="active">Aktif</option><option value="inactive">Pasif</option></select>
                    <button class="btn" type="submit" @disabled(!$tenantDbLabel)>İşletme Ekle</button>
                </div>
                <p style="margin:8px 0 0;font-size:12px;color:#64748b">İşletme giriş kullanıcı adı ve e-posta, kayıt sırasında sistem tarafından otomatik atanır.</p>
            </form>
            <div class="toolbar" style="margin:12px 0;"><input class="search" placeholder="İşletme ara..."><button class="btn" type="button">Sütunlar</button></div>
            <table>
                <thead><tr><th>ID</th><th>İşletme</th><th>Telefon</th><th>Adres</th><th>Durum</th><th>Aksiyonlar</th></tr></thead>
                <tbody>
                @forelse(($businesses ?? collect()) as $business)
                    <tr>
                        <td>{{ $business->id }}</td>
                        <td colspan="4">
                            <form method="post" action="{{ route('company.businesses.update', $business->id) }}" class="toolbar">
                                @csrf
                                <input name="name" value="{{ $business->name }}" required>
                                <input name="phone" value="{{ $business->phone }}" required>
                                <input name="address" value="{{ $business->address }}" required style="min-width:200px">
                                <select name="status">
                                    <option value="active" @selected($business->status === 'active')>Aktif</option>
                                    <option value="inactive" @selected($business->status === 'inactive')>Pasif</option>
                                </select>
                                <input type="password" name="login_password" placeholder="Yeni şifre (opsiyonel)">
                                <button class="btn" type="submit">Güncelle</button>
                            </form>
                        </td>
                        <td style="white-space:nowrap">
                            @php
                                $businessEntryUrl = route('business.login').'?'.http_build_query(array_filter([
                                    'company_username' => $company->login_username ?? '',
                                    'username' => $business->login_username ?? '',
                                ]));
                            @endphp
                            <a href="{{ $businessEntryUrl }}" class="btn" target="_blank" rel="noopener noreferrer" title="İşletme girişi (yeni sekme)">İşletme sayfası</a>
                            <form method="post" action="{{ route('company.businesses.delete', $business->id) }}" style="display:inline;margin-left:6px" onsubmit="return confirm('İşletme silinsin mi?')">
                                @csrf
                                <button class="btn" type="submit">Sil</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">İşletme kaydı yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        @elseif($page === 'kuryeler')
            @php
                $courierDurum = ['available' => 'Müsait', 'busy' => 'Meşgul', 'offline' => 'Çevrimdışı'];
            @endphp
            <h1>Kuryeler</h1>
            @if($tenantDbLabel)
                <p class="info-banner page-block">
                    Kurye kayıtları yalnızca kiracı veritabanına yazılır: <code>{{ $tenantDbLabel }}</code>
                </p>
            @else
                <p class="alert-err" style="margin-bottom:12px">Kiracı veritabanı tanımlı değil; kurye eklenemez.</p>
            @endif
            <div class="toolbar" style="margin:12px 0;">
                <button type="button" class="btn" @disabled(!$tenantDbLabel) onclick="document.getElementById('courier-add-dialog')?.showModal()">Kurye ekle</button>
            </div>
            <dialog id="courier-add-dialog" class="kurye-dialog" @if(!$tenantDbLabel) hidden @endif>
                <div class="kurye-dialog__head">Yeni kurye</div>
                <form method="post" action="{{ route('company.couriers.store') }}" class="kurye-dialog__body">
                    @csrf
                    <div class="kurye-dialog__row">
                        <label for="courier-name">Ad Soyad</label>
                        <input id="courier-name" name="name" required autocomplete="name">
                    </div>
                    <div class="kurye-dialog__row">
                        <label for="courier-phone">Telefon</label>
                        <input id="courier-phone" name="phone" required autocomplete="tel">
                    </div>
                    <div class="kurye-dialog__row">
                        <label for="courier-vehicle">Araç</label>
                        <select id="courier-vehicle" name="vehicle_type" required>
                            <option value="motosiklet">Motosiklet</option>
                            <option value="araba">Araba</option>
                            <option value="bisiklet">Bisiklet</option>
                            <option value="diger">Diğer</option>
                        </select>
                    </div>
                    <div class="kurye-dialog__row">
                        <label for="courier-status">Çalışma durumu</label>
                        <select id="courier-status" name="status">
                            <option value="available">Müsait</option>
                            <option value="busy">Meşgul</option>
                            <option value="offline">Çevrimdışı</option>
                        </select>
                    </div>
                    <div class="kurye-dialog__row">
                        <label for="courier-is-active">Hesap</label>
                        <select id="courier-is-active" name="is_active">
                            <option value="1" selected>Aktif</option>
                            <option value="0">Pasif</option>
                        </select>
                    </div>
                    <div class="kurye-dialog__row">
                        <label for="courier-pw">Giriş şifresi</label>
                        <input id="courier-pw" type="password" name="password" required autocomplete="new-password">
                    </div>
                    <div class="kurye-dialog__actions">
                        <button type="button" class="btn" onclick="document.getElementById('courier-add-dialog')?.close()">İptal</button>
                        <button type="submit" class="btn">Kaydet</button>
                    </div>
                </form>
            </dialog>
            <div class="toolbar" style="margin:12px 0;"><input class="search" placeholder="Kurye ara..."><button class="btn" type="button">Sütunlar</button></div>
            <table>
                <thead><tr><th>ID</th><th colspan="5">Ad · Telefon · Araç · Çalışma · Hesap · şifre</th><th>İşlemler</th></tr></thead>
                <tbody>
                @forelse($couriers as $c)
                    <tr>
                        <td>{{ $c->id }}</td>
                        <td colspan="5">
                            <form method="post" action="{{ route('company.couriers.update', $c->id) }}" class="toolbar" style="margin:0">
                                @csrf
                                <input name="name" value="{{ $c->name }}" required>
                                <input name="phone" value="{{ $c->phone }}" required>
                                <select name="vehicle_type">
                                    @foreach(['motosiklet' => 'Motosiklet','araba' => 'Araba','bisiklet' => 'Bisiklet','diger' => 'Diğer'] as $val => $label)
                                        <option value="{{ $val }}" @selected($c->vehicle_type === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <select name="status" title="Çalışma durumu (müsait/meşgul)">
                                    @foreach($courierDurum as $val => $label)
                                        <option value="{{ $val }}" @selected($c->status === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <select name="is_active" title="Hesap aktif veya pasif">
                                    <option value="1" @selected(($c->is_active ?? true))>Aktif</option>
                                    <option value="0" @selected(!($c->is_active ?? true))>Pasif</option>
                                </select>
                                <input type="password" name="password" placeholder="Yeni şifre (opsiyonel)">
                                <button class="btn" type="submit">Güncelle</button>
                            </form>
                        </td>
                        <td>
                            <form method="post" action="{{ route('company.couriers.destroy', $c->id) }}" style="display:inline" onsubmit="return confirm('Bu kurye silinsin mi?');">
                                @csrf
                                <button class="btn" type="submit" style="color:#b91c1c;border-color:#fecaca">Sil</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Henüz kurye kaydı yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        @else
            <h1>
                {{ [
                    'basvurular-restoran' => 'Başvurular / Firma',
                    'basvurular-kurye' => 'Başvurular / Kurye',
                    'harita' => 'Harita',
                    'cari-hesap' => 'Cari Hesap',
                    'raporlar' => 'Raporlar',
                    'kurye-raporlari' => 'Kurye Raporları',
                    'restoran-raporlari' => 'Firma raporları',
                    'yonetim' => 'Yönetim',
                    'ayarlar' => 'Ayarlar',
                    'kontor-yukle' => 'Kontör Yükle',
                ][$page] ?? 'Modül' }}
            </h1>
            <p style="color:var(--muted)">Bu ekran referans menü yapısına göre hazırlandı. Sonraki adımda detay iş kuralları bağlanacak.</p>
        @endif
        </div>
    </main>
</div>
<script>
    window.applyGdColumnPrefs = function () {
        const defaults = {
            time: true, order: true, business: true, customer: true,
            payment: true, neighborhood: true, status: true, staff: true,
        };
        let prefs = { ...defaults };
        try {
            const raw = localStorage.getItem('companyGdOrderCols');
            if (raw) {
                prefs = { ...defaults, ...JSON.parse(raw) };
            }
        } catch (e) { /* ignore */ }
        document.querySelectorAll('.tbl-orders [data-oc]').forEach((el) => {
            const k = el.getAttribute('data-oc');
            if (!k || !(k in prefs)) return;
            el.classList.toggle('is-col-hidden', prefs[k] === false);
        });
        document.querySelectorAll('[data-col-pref]').forEach((inp) => {
            const k = inp.getAttribute('data-col-pref');
            if (k && k in prefs) inp.checked = prefs[k] !== false;
        });
    };

    window.openGdColumnsDialog = function () {
        window.applyGdColumnPrefs();
        document.getElementById('gd-columns-dialog')?.showModal();
    };

    window.saveGdColumns = function () {
        const prefs = {};
        document.querySelectorAll('[data-col-pref]').forEach((inp) => {
            const k = inp.getAttribute('data-col-pref');
            if (k) prefs[k] = inp.checked;
        });
        if (!Object.values(prefs).some(Boolean)) {
            alert('En az bir sütun görünür olmalı.');
            return;
        }
        localStorage.setItem('companyGdOrderCols', JSON.stringify(prefs));
        window.applyGdColumnPrefs();
        document.getElementById('gd-columns-dialog')?.close();
    };

    window.syncGdStatsPanel = function () {
        const cb = document.getElementById('gd-stats-toggle');
        const p = document.getElementById('gd-stats-panel');
        if (!cb || !p) return;
        const open = localStorage.getItem('companyGdStatsOpen') === '1';
        cb.checked = open;
        p.hidden = !open;
    };

    window.companyOpenAssignCourier = function (btn) {
        if (!btn) return;
        const dialog = document.getElementById('assign-courier-dialog');
        const form = document.getElementById('assign-courier-form');
        if (!dialog || !form) return;
        const id = btn.getAttribute('data-order-id');
        const base = (form.getAttribute('data-assign-base') || '').replace(/\/$/, '');
        if (base && id) {
            form.action = base + '/' + encodeURIComponent(id) + '/kurye';
        }
        const labelEl = document.getElementById('assign-courier-order-label');
        if (labelEl) {
            labelEl.textContent = btn.getAttribute('data-order-label') || ('#' + id);
        }
        form.querySelectorAll('input[name="courier_id"][type="radio"]').forEach((r) => { r.checked = false; });
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            alert('Tarayıcınız pencere (dialog) özelliğini desteklemiyor. Lütfen güncel Chrome veya Edge kullanın.');
        }
    };

    (function () {
        const contentEl = document.querySelector('.content');
        if (!contentEl) return;

        const syncCompanyManualOrderForm = () => {
            const rs = document.getElementById('co-restaurant');
            const cust = document.getElementById('co-customer');
            if (!rs || !cust) return;
            const rid = rs.value;
            cust.querySelectorAll('option[data-restaurant]').forEach((opt) => {
                opt.disabled = Boolean(rid) && opt.getAttribute('data-restaurant') !== rid;
            });
            const sel = cust.options[cust.selectedIndex];
            if (sel && sel.disabled) cust.value = '';
            const showNew = !cust.value;
            document.querySelectorAll('.co-new-cust').forEach((el) => { el.style.display = showNew ? '' : 'none'; });
            document.getElementById('co-name')?.toggleAttribute('required', showNew);
            document.getElementById('co-phone')?.toggleAttribute('required', showNew);
        };

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
                syncCompanyManualOrderForm();
                if (typeof window.applyGdColumnPrefs === 'function') window.applyGdColumnPrefs();
                if (typeof window.syncGdStatsPanel === 'function') window.syncGdStatsPanel();
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

        document.addEventListener('change', (e) => {
            const t = e.target;
            if (t && (t.id === 'co-restaurant' || t.id === 'co-customer')) {
                syncCompanyManualOrderForm();
            }
        });

        document.addEventListener('input', (e) => {
            const t = e.target;
            if (!t || t.id !== 'company-order-search') return;
            const q = String(t.value || '').trim().toLowerCase();
            document.querySelectorAll('[data-order-search]').forEach((row) => {
                const hay = (row.getAttribute('data-order-search') || '').toLowerCase();
                row.style.display = !q || hay.includes(q) ? '' : 'none';
            });
        });

        document.addEventListener('change', (e) => {
            const t = e.target;
            if (t && t.id === 'gd-stats-toggle') {
                const p = document.getElementById('gd-stats-panel');
                if (p) p.hidden = !t.checked;
                localStorage.setItem('companyGdStatsOpen', t.checked ? '1' : '0');
            }
        });

        document.addEventListener('click', (e) => {
            if (e.target.closest('#gd-columns-save')) {
                e.preventDefault();
                window.saveGdColumns();
            }
        });

        syncCompanyManualOrderForm();
        if (typeof window.applyGdColumnPrefs === 'function') window.applyGdColumnPrefs();
        if (typeof window.syncGdStatsPanel === 'function') window.syncGdStatsPanel();

    })();
</script>
</body>
</html>
