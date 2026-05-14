<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>İşletme Paneli</title>
    @include('partials.theme-fonts')
    <style>
        @include('partials.theme-css-unified')
        @include('partials.theme-css-business')
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="sidebar__brand">
            <span class="sidebar__badge">İşletme</span>
            <h3>{{ $business?->name ?? 'İşletme Paneli' }}</h3>
            <p class="business-brand-sub">{{ $company->company_name }}</p>
        </div>
        <nav class="sidebar__nav" aria-label="Ana menü">
        <ul class="menu-root">
            <li><a class="single-link nav-link" href="{{ route('business.dashboard') }}">Güncel Durum</a></li>
            <li><a class="single-link nav-link" href="{{ route('business.page', 'masa-adisyonlar') }}">Masa Adisyonlar</a></li>

            <li>
                <details open>
                    <summary><span class="arrow">▶</span><span>Gelirler</span></summary>
                    <ul class="submenu">
                        <li><a class="nav-link" href="{{ route('business.page', 'gelirler-siparisler') }}">Siparişler</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'siparisler-teslim-edilen') }}">Teslim Edilenler</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'siparisler-iptal-edilen') }}">İptal Edilenler</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'musteriler') }}">Müşteriler</a></li>
                    </ul>
                </details>
            </li>

            <li>
                <details>
                    <summary><span class="arrow">▶</span><span>Ürünler ve Menüler</span></summary>
                    <ul class="submenu">
                        <li><a class="nav-link" href="{{ route('business.page', 'urunler') }}">Ürünler</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'kategoriler') }}">Kategoriler</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'kategori-gruplari') }}">Kategori Grupları</a></li>
                    </ul>
                </details>
            </li>

            <li>
                <details>
                    <summary><span class="arrow">▶</span><span>Masraf Yönetimi</span></summary>
                    <ul class="submenu">
                        <li><a class="nav-link" href="{{ route('business.page', 'masraflar') }}">Masraflar</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'masraf-kategorileri') }}">Masraf Kategorileri</a></li>
                    </ul>
                </details>
            </li>

            <li>
                <details>
                    <summary><span class="arrow">▶</span><span>Raporlar</span></summary>
                    <ul class="submenu">
                        <li><a class="nav-link" href="{{ route('business.page', 'raporlar-genel') }}">Genel Raporlar</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'raporlar-kasa') }}">Kasa Raporları</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'raporlar-kurye-tahsilatlari') }}">Kurye Tahsilatları</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'raporlar-urun-performansi') }}">Ürün Performansı</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'raporlar-haritada') }}">Haritada</a></li>
                    </ul>
                </details>
            </li>

            <li>
                <details>
                    <summary><span class="arrow">▶</span><span>Ayarlar</span></summary>
                    <ul class="submenu">
                        <li><a class="nav-link" href="{{ route('business.page', 'ayarlar-genel') }}">Genel Ayarlar</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'ayarlar-adisyon') }}">Adisyon Ayarları</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'ayarlar-yazici') }}">Yazıcı Ayarları</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'ayarlar-garson') }}">Garson Ayarları</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'ayarlar-operasyon') }}">Operasyon Ayarları</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'ayarlar-loji-team') }}">Loji Team</a></li>
                    </ul>
                </details>
            </li>

            <li>
                <details>
                    <summary><span class="arrow">▶</span><span>Entegrasyonlar</span></summary>
                    <ul class="submenu">
                        <li><a class="nav-link" href="{{ route('business.page', 'entegrasyonlar-yemeksepeti') }}">Yemeksepeti</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'entegrasyonlar-getir-yemek') }}">Getir Yemek</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'entegrasyonlar-trendyol-yemek') }}">Trendyol Yemek</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'entegrasyonlar-migros-yemek') }}">Migros Yemek</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'entegrasyonlar-roogo-yemek') }}">RooGo Yemek</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'diger-entegrasyonlar') }}">Diğer Entegrasyonlar</a></li>
                    </ul>
                </details>
            </li>

            <li>
                <details>
                    <summary><span class="arrow">▶</span><span>ÖKC Entegrasyonları</span></summary>
                    <ul class="submenu">
                        <li><a class="nav-link" href="{{ route('business.page', 'okc-pavo') }}">PAVO</a></li>
                    </ul>
                </details>
            </li>

            <li>
                <details>
                    <summary><span class="arrow">▶</span><span>Yardım</span></summary>
                    <ul class="submenu">
                        <li><a class="nav-link" href="{{ route('business.page', 'yardim-egitim-kanali') }}">Eğitim Kanalı</a></li>
                        <li><a class="nav-link" href="{{ route('business.page', 'yardim-anydesk') }}">AnyDesk</a></li>
                    </ul>
                </details>
            </li>
        </ul>
        </nav>

        <div class="sidebar__footer">
            <form method="post" action="{{ route('business.logout') }}">
                @csrf
                <button class="btn" type="submit">Çıkış</button>
            </form>
        </div>
    </aside>

    <main class="content">
        @php
            $integrationConfigs = [
                'entegrasyonlar-yemeksepeti' => [
                    'provider' => 'yemeksepeti',
                    'title' => 'Yemeksepeti Entegrasyon',
                    'desc' => 'Yemeksepeti entegrasyonlarınızı bu ekrandan yönetebilirsiniz.',
                    'columns' => ['Firma adı', 'Zincir ID', 'Firma remote ID', 'Menü', 'İşlem'],
                    'fields' => [
                        ['label' => 'Firma adı', 'name' => 'restaurant_name', 'placeholder' => 'Firma adı'],
                        ['label' => 'Zincir ID', 'name' => 'chain_id', 'placeholder' => 'Zincir ID'],
                        ['label' => 'Firma remote ID', 'name' => 'restaurant_remote_id', 'placeholder' => 'Firma remote ID', 'hint' => "Firma paketservis.app'tan gelmiyorsa boş bırakınız."],
                        ['label' => 'Firma URL', 'name' => 'restaurant_url', 'placeholder' => 'Firma URL'],
                    ],
                ],
                'entegrasyonlar-getir-yemek' => [
                    'provider' => 'getir-yemek',
                    'title' => 'Getir Yemek Entegrasyon',
                    'desc' => 'Getir Yemek entegrasyonlarınızı bu ekrandan yönetebilirsiniz.',
                    'columns' => ['Firma adı', 'Firma gizli anahtarı', 'Menü', 'İşlem'],
                    'fields' => [
                        ['label' => 'Firma adı', 'name' => 'restaurant_name', 'placeholder' => 'Firma adı'],
                        ['label' => 'Firma gizli anahtarı', 'name' => 'restaurant_secret', 'placeholder' => 'Firma gizli anahtarı'],
                    ],
                ],
                'entegrasyonlar-trendyol-yemek' => [
                    'provider' => 'trendyol-yemek',
                    'title' => 'Trendyol Yemek Entegrasyon',
                    'desc' => 'Trendyol Yemek entegrasyonlarınızı bu ekrandan yönetebilirsiniz.',
                    'columns' => ['Firma adı', 'Satıcı ID', 'Firma ID', 'Menü', 'İşlem'],
                    'fields' => [
                        ['label' => 'Firma adı', 'name' => 'restaurant_name', 'placeholder' => 'Firma adı'],
                        ['label' => 'Firma anahtarı', 'name' => 'restaurant_key', 'placeholder' => 'Firma anahtarı', 'hint' => "paketservis.app tarafındaki API Key"],
                        ['label' => 'Firma gizli anahtarı', 'name' => 'restaurant_secret', 'placeholder' => 'Firma gizli anahtarı', 'hint' => "paketservis.app tarafındaki API Secret"],
                        ['label' => 'Satıcı ID', 'name' => 'seller_id', 'placeholder' => 'Satıcı ID'],
                        ['label' => 'Firma ID', 'name' => 'restaurant_id', 'placeholder' => 'Firma ID'],
                    ],
                ],
                'entegrasyonlar-migros-yemek' => [
                    'provider' => 'migros-yemek',
                    'title' => 'Migros Yemek Entegrasyon',
                    'desc' => 'Migros Yemek entegrasyonlarınızı bu ekrandan yönetebilirsiniz.',
                    'columns' => ['Firma adı', 'Zincir ID', 'Firma ID', 'Menü', 'İşlem'],
                    'fields' => [
                        ['label' => 'Firma adı', 'name' => 'restaurant_name', 'placeholder' => 'Firma adı'],
                        ['label' => 'Firma anahtarı', 'name' => 'restaurant_key', 'placeholder' => 'Firma anahtarı', 'hint' => "paketservis.app tarafındaki API Key"],
                        ['label' => 'Zincir ID', 'name' => 'chain_id', 'placeholder' => 'Zincir ID'],
                        ['label' => 'Firma ID', 'name' => 'restaurant_id', 'placeholder' => 'Firma ID'],
                    ],
                ],
            ];
            $integrationPage = $integrationConfigs[$page] ?? null;
        @endphp
        @if(session('ok'))
            <div class="section" style="margin-bottom:10px;background:#ecfeff;border-color:#bae6fd;color:#0f766e">{{ session('ok') }}</div>
        @endif
        @if(session('error'))
            <div class="section" style="margin-bottom:10px;background:#fef2f2;border-color:#fecaca;color:#991b1b">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="section" style="margin-bottom:10px;background:#fff7ed;border-color:#fed7aa;color:#9a3412">
                <strong>Lütfen formu kontrol edin:</strong>
                <ul style="margin:8px 0 0 18px;padding:0">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="content-inner-card">
        <h1>
            @switch($page)
                @case('guncel-durum') Güncel Durum @break
                @case('masa-adisyonlar') Masa Adisyonlar @break
                @case('gelirler-siparisler') Siparişler @break
                @case('siparisler-teslim-edilen') Teslim Edilen Siparişler @break
                @case('siparisler-iptal-edilen') İptal Edilen Siparişler @break
                @case('urunler') Ürünler @break
                @case('kategoriler') Kategoriler @break
                @case('kategori-gruplari') Kategori Grupları @break
                @case('masraflar') Masraflar @break
                @case('masraf-kategorileri') Masraf Kategorileri @break
                @case('musteriler') Müşteriler @break
                @case('raporlar-genel') Genel Raporlar @break
                @case('raporlar-kasa') Kasa Raporları @break
                @case('raporlar-kurye-tahsilatlari') Kurye Tahsilatları @break
                @case('raporlar-urun-performansi') Ürün Performansı @break
                @case('raporlar-haritada') Haritada @break
                @case('ayarlar-genel') Genel Ayarlar @break
                @case('ayarlar-adisyon') Adisyon Ayarları @break
                @case('ayarlar-yazici') Yazıcı Ayarları @break
                @case('ayarlar-garson') Garson Ayarları @break
                @case('ayarlar-operasyon') Operasyon Ayarları @break
                @case('ayarlar-loji-team') Loji Team @break
                @case('entegrasyonlar-yemeksepeti') Yemeksepeti @break
                @case('entegrasyonlar-getir-yemek') Getir Yemek @break
                @case('entegrasyonlar-trendyol-yemek') Trendyol Yemek @break
                @case('entegrasyonlar-migros-yemek') Migros Yemek @break
                @case('entegrasyonlar-roogo-yemek') RooGo Yemek @break
                @case('diger-entegrasyonlar') Diğer Entegrasyonlar @break
                @case('okc-pavo') PAVO @break
                @case('yardim-egitim-kanali') Eğitim Kanalı @break
                @case('yardim-anydesk') AnyDesk @break
                @default Panel
            @endswitch
        </h1>

        <div class="cards">
            <div class="card"><span class="k">Yeni Sipariş</span><span class="v">{{ $stats['new_orders'] }}</span></div>
            <div class="card"><span class="k">Aktif Sipariş</span><span class="v">{{ $stats['active_orders'] }}</span></div>
            <div class="card"><span class="k">Kurye Bekleyen</span><span class="v">{{ $stats['ready_orders'] }}</span></div>
            <div class="card"><span class="k">Bugün Teslim</span><span class="v">{{ $stats['delivered_today'] }}</span></div>
            <div class="card"><span class="k">Bugün İptal</span><span class="v">{{ $stats['cancelled_today'] }}</span></div>
        </div>

        @if($page === 'siparisler-teslim-edilen' || $page === 'siparisler-iptal-edilen')
            <p class="desc">Anlık gelen siparişlerinizi <a class="muted-link nav-link" href="{{ route('business.dashboard') }}">Güncel Durum</a> sayfasından görüntüleyebilirsiniz.</p>
            <div class="toolbar">
                <input type="text" value="{{ now()->format('M d, Y') }} 00:00" style="max-width:220px">
                <input type="text" value="{{ now()->format('M d, Y') }} 23:59" style="max-width:220px">
                @if($page === 'siparisler-teslim-edilen')
                    <select><option>Tüm Sipariş Kanalları</option></select>
                    <select><option>Tüm Personeller</option></select>
                    <select><option>Ödeme Yöntemi</option></select>
                @endif
            </div>
            <div class="kpi-3">
                <div class="item"><div class="label">Paket Sayısı</div><div class="value">0</div></div>
                <div class="item"><div class="label">Paket Ortalama Tutarı</div><div class="value">0,00 ₺</div></div>
                <div class="item"><div class="label">Toplam Satış Tutarı</div><div class="value">0,00 ₺</div></div>
            </div>
            <div class="section">
                <div class="toolbar">
                    <input placeholder="Ara" style="min-width:320px">
                    <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
                        <span style="color:#64748b">Sayfa başına satır:</span>
                        <select><option>25</option><option>50</option></select>
                        <button class="btn-ghost" type="button">Sütunlar</button>
                    </div>
                </div>
                <table>
                    <thead>
                    <tr><th>☰</th><th>Tarih</th><th>Müşteri Adı</th><th>Tutar</th><th>Mahalle</th><th>Sipariş Kanalı</th><th>Ödeme Yöntemi</th><th>Personel</th><th>Taşıma Bedeli</th><th>İşlem</th></tr>
                    </thead>
                    <tbody>
                    <tr><td colspan="10" style="text-align:center;padding:34px;color:#64748b">Kayıt bulunamadı.</td></tr>
                    </tbody>
                </table>
                <div class="pagination">
                    <button class="btn-ghost" type="button">« Önceki</button>
                    <button class="btn-ghost" type="button">Sonraki »</button>
                </div>
            </div>
        @elseif($page === 'musteriler')
            @php
                $ceOld = old('customer_edit_id');
                $customerFormAction = ($ceOld !== null && $ceOld !== '') ? route('business.customers.update', ['customer' => (int) $ceOld]) : route('business.customers.store');
                $openCustomerModal = $errors->any() && ! old('_manual_order');
            @endphp
            <p class="desc">Bu ekrandan kendi müşterilerinizi ve platform müşterinizi yönetebilirsiniz.</p>
            <div class="space-between" style="margin-bottom:10px">
                <div></div>
                <div style="display:flex;gap:8px">
                    <button class="btn-ghost" type="button">Tüm Müşterileri Sil</button>
                    <button class="btn-success" type="button" data-open-customer-modal>Müşteri Ekle</button>
                </div>
            </div>
            <div class="section">
                <div class="toolbar">
                    <input placeholder="Ara" style="min-width:320px">
                    <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
                        <span style="color:#64748b">Sayfa başına satır:</span>
                        <select><option>25</option><option>50</option></select>
                        <button class="btn-ghost" type="button">Sütunlar</button>
                    </div>
                </div>
                <table>
                    <thead><tr><th>ID</th><th>Müşteri</th><th>Sipariş Sayısı</th><th>Telefon</th><th>İlçe</th><th>İşlem</th></tr></thead>
                    <tbody>
                    @forelse($customers as $customer)
                        @php
                            $dirOneLine = preg_replace('/\s+/', ' ', trim((string) ($customer->latest_addr_directions ?? '')));
                        @endphp
                        <tr>
                            <td>{{ $customer->id }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->orders_count ?? 0 }}</td>
                            <td>{{ $customer->phone }}</td>
                            <td>{{ $customer->district ?? '—' }}</td>
                            <td style="white-space:nowrap">
                                <button type="button" class="btn-ghost" style="padding:6px 10px;font-size:13px"
                                    data-edit-customer
                                    data-id="{{ $customer->id }}"
                                    data-name="{{ e($customer->name) }}"
                                    data-email="{{ e($customer->email ?? '') }}"
                                    data-phone="{{ e($customer->phone) }}"
                                    data-phone2="{{ e($customer->phone_2 ?? '') }}"
                                    data-note="{{ e($customer->note ?? '') }}"
                                    data-addr-title="{{ e($customer->latest_addr_title ?? '') }}"
                                    data-addr-line="{{ e($customer->latest_addr_line ?? '') }}"
                                    data-addr-district="{{ e($customer->latest_addr_district ?? '') }}"
                                    data-addr-city="{{ e($customer->latest_addr_city ?? '') }}"
                                    data-addr-directions="{{ e($dirOneLine) }}"
                                >Düzenle</button>
                                <form method="post" action="{{ route('business.customers.destroy', $customer->id) }}" style="display:inline;margin-left:4px" onsubmit="return confirm('Bu müşteri silinsin mi? Adres kayıtları da silinir.');">
                                    @csrf
                                    <button type="submit" class="btn-ghost" style="padding:6px 10px;font-size:13px;color:#b91c1c;border-color:#fecaca">Sil</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;padding:34px;color:#64748b">Kayıt bulunamadı.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;color:#64748b">
                    <span>{{ $customers->count() }} satırın {{ $customers->count() }} tanesi gösteriliyor. Sayfa 1/1</span>
                    <div class="pagination" style="padding:0">
                        <button class="btn-ghost" type="button">«</button>
                        <button class="btn-ghost" type="button">‹ Önceki</button>
                        <button class="btn-success" type="button" style="padding:8px 14px">1</button>
                        <button class="btn-ghost" type="button">Sonraki ›</button>
                        <button class="btn-ghost" type="button">»</button>
                    </div>
                </div>
            </div>

            <div class="modal @if($openCustomerModal) open @endif" id="customer-modal">
                <div class="modal-card" style="width:min(760px,95vw)">
                    <div class="modal-head">
                        <h3 id="customer-modal-title">@if(old('customer_edit_id')) Müşteri düzenle @else Müşteri ekle @endif</h3>
                        <button type="button" class="icon-btn" data-close-customer-modal>&times;</button>
                    </div>
                    <form id="customer-form" class="form-grid" method="post" action="{{ $customerFormAction }}">
                        @csrf
                        <input type="hidden" name="customer_edit_id" id="fld-customer-edit-id" value="{{ old('customer_edit_id', '') }}">
                        <div class="two-col">
                            <div>
                                <label>Müşteri Adı *</label>
                                <input name="name" value="{{ old('name') }}" placeholder="Müşteri Adı" required>
                            </div>
                            <div>
                                <label>E-posta</label>
                                <input name="email" value="{{ old('email') }}" placeholder="E-posta">
                            </div>
                            <div>
                                <label>Telefon 1 *</label>
                                <input name="phone_1" value="{{ old('phone_1') }}" placeholder="Telefon 1" required>
                            </div>
                            <div>
                                <label>Telefon 2</label>
                                <input name="phone_2" value="{{ old('phone_2') }}" placeholder="Telefon 2">
                            </div>
                        </div>
                        <div>
                            <label>Müşteri Notu</label>
                            <input name="note" value="{{ old('note') }}" placeholder="Müşteri Notu">
                        </div>
                        <fieldset class="address-fieldset" style="border:1px solid var(--line);border-radius:10px;padding:12px;margin:0">
                            <legend style="padding:0 8px;font-weight:600;font-size:14px">Adres *</legend>
                            <p style="margin:0 0 10px;font-size:12px;color:#64748b">Manuel siparişteki teslimat adresi alanları ile aynıdır; müşteri kaydına adres olarak eklenir.</p>
                            <div class="two-col">
                                <div style="grid-column:1/-1">
                                    <label for="cust-addr-title">Adres başlığı</label>
                                    <input id="cust-addr-title" name="addr_title" value="{{ old('addr_title') }}" placeholder="Ev, İş…" maxlength="120">
                                </div>
                                <div style="grid-column:1/-1">
                                    <label for="cust-addr-line">Açık adres (mahalle, sokak, bina, daire) *</label>
                                    <input id="cust-addr-line" name="addr_line" value="{{ old('addr_line') }}" required maxlength="255" placeholder="Örn. Yıldız Mah. Atatürk Cad. No:12 D:4">
                                </div>
                                <div>
                                    <label for="cust-addr-district">İlçe *</label>
                                    <input id="cust-addr-district" name="addr_district" value="{{ old('addr_district') }}" required maxlength="120" placeholder="İlçe">
                                </div>
                                <div>
                                    <label for="cust-addr-city">İl *</label>
                                    <input id="cust-addr-city" name="addr_city" value="{{ old('addr_city') }}" required maxlength="120" placeholder="İl">
                                </div>
                                <div style="grid-column:1/-1">
                                    <label for="cust-addr-dir">Adres tarifi (opsiyonel)</label>
                                    <textarea id="cust-addr-dir" name="addr_directions" maxlength="300" placeholder="Kapı şifresi, renkli bina, yakın durak…">{{ old('addr_directions') }}</textarea>
                                </div>
                            </div>
                        </fieldset>
                        <div class="modal-actions">
                            <button type="button" class="btn-ghost" data-close-customer-modal>Vazgeç</button>
                            <button type="submit" class="btn-success" id="customer-form-submit">@if(old('customer_edit_id')) Güncelle @else Kaydet @endif</button>
                        </div>
                    </form>
                </div>
            </div>
        @elseif(str_starts_with($page, 'siparisler') || $page === 'guncel-durum' || $page === 'gelirler-siparisler')
            @php
                $orderStatusTr = [
                    'pending' => 'Yeni sipariş',
                    'restaurant_preparing' => 'Hazırlanıyor',
                    'ready_for_courier' => 'Kurye bekliyor',
                    'courier_assigned' => 'Kurye atandı',
                    'courier_picked' => 'Kuryede',
                    'delivered' => 'Teslim edildi',
                    'cancelled' => 'İptal',
                ];
            @endphp
            <div class="toolbar">
                <input placeholder="Sipariş no / müşteri ara">
                <select>
                    <option>Son 24 Saat</option>
                    <option>Son 7 Gün</option>
                    <option>Bu Ay</option>
                </select>
                <button class="btn" type="button">Filtre Uygula</button>
                <button class="btn-success" type="button" data-open-manual-order-modal style="margin-left:auto">Şubeden sipariş gir</button>
            </div>
            <p class="desc" style="margin-top:0;margin-bottom:12px">Entegrasyon dışında, işletmeye gelen müşteriler için siparişi buradan kaydedebilirsiniz; kayıtlar aynı sipariş listesinde görünür.</p>
            <div class="modal @if($errors->any() && old('_manual_order')) open @endif" id="manual-order-modal">
                <div class="modal-card" style="width:min(760px,95vw)">
                    <div class="modal-head">
                        <h3>Manuel sipariş</h3>
                        <button type="button" class="icon-btn" data-close-manual-order-modal>&times;</button>
                    </div>
                    <form id="manual-order-form" class="form-grid" method="post" action="{{ route('business.orders.manual.store') }}">
                        @csrf
                        <input type="hidden" name="_manual_order" value="1">
                        <div>
                            <label for="mo-pick-customer">Kayıtlı müşteri (hızlı seçim)</label>
                            <select id="mo-pick-customer" name="customer_id">
                                <option value="">— Yeni müşteri (ad ve telefonu aşağıya yazın) —</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" data-name="{{ e($c->name) }}" data-phone="{{ e($c->phone) }}" @selected((string) old('customer_id') === (string) $c->id)>{{ $c->name }} — {{ $c->phone }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="two-col">
                            <div>
                                <label for="mo-name">Müşteri adı *</label>
                                <input id="mo-name" name="customer_name" value="{{ old('customer_name') }}" maxlength="120" autocomplete="name">
                            </div>
                            <div>
                                <label for="mo-phone">Telefon *</label>
                                <input id="mo-phone" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="32" autocomplete="tel">
                            </div>
                        </div>
                        <p style="margin:0;font-size:12px;color:#64748b">Kayıtlı müşteri seçilirse ad ve telefon otomatik dolar. Teslimat adresi müşteri kaydı ile aynı alanlardan girilir; kayıt müşteri rehberine ve siparişe yazılır.</p>
                        <fieldset class="address-fieldset" style="border:1px solid var(--line);border-radius:10px;padding:12px;margin:0">
                            <legend style="padding:0 8px;font-weight:600;font-size:14px">Teslimat adresi *</legend>
                            <div class="two-col">
                                <div style="grid-column:1/-1">
                                    <label for="mo-addr-title">Adres başlığı</label>
                                    <input id="mo-addr-title" name="addr_title" value="{{ old('addr_title') }}" placeholder="Ev, İş…" maxlength="120">
                                </div>
                                <div style="grid-column:1/-1">
                                    <label for="mo-addr-line">Açık adres (mahalle, sokak, bina, daire) *</label>
                                    <input id="mo-addr-line" name="addr_line" value="{{ old('addr_line') }}" required maxlength="255" placeholder="Örn. Yıldız Mah. Atatürk Cad. No:12 D:4">
                                </div>
                                <div>
                                    <label for="mo-addr-district">İlçe *</label>
                                    <input id="mo-addr-district" name="addr_district" value="{{ old('addr_district') }}" required maxlength="120" placeholder="İlçe">
                                </div>
                                <div>
                                    <label for="mo-addr-city">İl *</label>
                                    <input id="mo-addr-city" name="addr_city" value="{{ old('addr_city') }}" required maxlength="120" placeholder="İl">
                                </div>
                                <div style="grid-column:1/-1">
                                    <label for="mo-addr-dir">Adres tarifi (opsiyonel)</label>
                                    <textarea id="mo-addr-dir" name="addr_directions" maxlength="300" placeholder="Kapı şifresi, renkli bina, yakın durak…">{{ old('addr_directions') }}</textarea>
                                </div>
                            </div>
                        </fieldset>
                        <div>
                            <label for="mo-price">Tutar (₺) *</label>
                            <input id="mo-price" name="price" type="number" step="0.01" min="0.01" value="{{ old('price') }}" required inputmode="decimal" placeholder="örn. 249,90">
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn-ghost" data-close-manual-order-modal>Vazgeç</button>
                            <button type="submit" class="btn-success">Siparişi kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
            <table>
                <thead>
                <tr><th>#</th><th>Sipariş No</th><th>Kaynak</th><th>Müşteri</th><th>Durum</th><th>Tutar</th><th>Tarih</th></tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ ($order->source ?? null) === 'manual' ? 'Şube' : (($order->source ?? null) ? $order->source : '—') }}</td>
                        <td>{{ $order->customer_name }}<br><span style="color:#64748b;font-size:12px">{{ $order->customer_phone }}</span></td>
                        <td>{{ $orderStatusTr[$order->status] ?? $order->status }}</td>
                        <td>{{ number_format((float) $order->price, 2, ',', '.') }} ₺</td>
                        <td>{{ $order->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Henüz sipariş kaydı yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        @elseif($page === 'urunler')
            <div class="toolbar">
                <input placeholder="Ürün ara">
                <select><option>Tüm Kategoriler</option><option>Döner</option><option>İçecek</option></select>
                <button class="btn" type="button">Yeni Ürün</button>
            </div>
            <table>
                <thead><tr><th>Ürün</th><th>Kategori</th><th>Fiyat</th><th>Durum</th><th>Aksiyon</th></tr></thead>
                <tbody>
                @foreach($products as $product)
                    <tr>
                        <td>{{ $product['name'] }}</td>
                        <td>{{ $product['category'] }}</td>
                        <td>{{ $product['price'] }}</td>
                        <td>{{ $product['status'] }}</td>
                        <td><button class="btn" type="button">Düzenle</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @elseif($page === 'kategoriler' || $page === 'kategori-gruplari')
            <div class="section">
                <h3>Kategori Yönetimi</h3>
                <div class="toolbar">
                    <input placeholder="Kategori adı">
                    <button class="btn" type="button">Kategori Ekle</button>
                </div>
                <table>
                    <thead><tr><th>Kategori</th><th>Ürün Sayısı</th><th>Durum</th></tr></thead>
                    <tbody>
                    <tr><td>Döner</td><td>7</td><td>Aktif</td></tr>
                    <tr><td>İçecek</td><td>12</td><td>Aktif</td></tr>
                    <tr><td>Tatlı</td><td>3</td><td>Pasif</td></tr>
                    </tbody>
                </table>
            </div>
        @elseif($page === 'masraflar' || $page === 'masraf-kategorileri')
            <div class="toolbar">
                <input placeholder="Masraf başlığı">
                <select><option>Sarf</option><option>Operasyon</option><option>Genel</option></select>
                <input placeholder="Tutar">
                <button class="btn" type="button">Masraf Ekle</button>
            </div>
            <table>
                <thead><tr><th>Başlık</th><th>Kategori</th><th>Tutar</th><th>Tarih</th></tr></thead>
                <tbody>
                @foreach($expenses as $expense)
                    <tr>
                        <td>{{ $expense['title'] }}</td>
                        <td>{{ $expense['category'] }}</td>
                        <td>{{ $expense['amount'] }}</td>
                        <td>{{ $expense['date'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @elseif(str_starts_with($page, 'raporlar'))
            <div class="section">
                <h3>Rapor Özeti</h3>
                <div class="cards" style="margin:0">
                    <div class="card"><span class="k">Toplam Sipariş</span><span class="v">{{ $orders->count() }}</span></div>
                    <div class="card"><span class="k">Ortalama Tutar</span><span class="v">₺{{ number_format((float) $orders->avg('price'), 2) }}</span></div>
                    <div class="card"><span class="k">İptal Oranı</span><span class="v">%{{ $stats['cancelled_today'] > 0 ? 12 : 0 }}</span></div>
                    <div class="card"><span class="k">Teslim Başarı</span><span class="v">%93</span></div>
                    <div class="card"><span class="k">Kurye Tahsilat</span><span class="v">₺18,420</span></div>
                </div>
            </div>
        @elseif($integrationPage)
            <div class="section">
                <div class="space-between" style="margin-bottom:10px">
                    <div>
                        <h3>{{ $integrationPage['title'] }}</h3>
                        <p style="margin:0;color:#64748b">{{ $integrationPage['desc'] }}</p>
                    </div>
                    <button class="btn-success" type="button" data-open-integration-modal>Entegrasyon Ekle</button>
                </div>
                <div class="toolbar">
                    <input placeholder="Ara">
                    <select><option>Sayfa başına satır: 10</option></select>
                    <button class="btn-ghost" type="button">Sütunlar</button>
                </div>
                <table>
                    <thead>
                    <tr>
                        @foreach($integrationPage['columns'] as $col)
                            <th>{{ $col }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        @foreach($integrationPage['columns'] as $idx => $col)
                            <td>{{ $idx === 0 ? '-' : '' }}</td>
                        @endforeach
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="modal" id="integration-modal">
                <div class="modal-card">
                    <div class="modal-head">
                        <h3>Entegrasyon ekle</h3>
                        <button type="button" class="icon-btn" data-close-integration-modal>&times;</button>
                    </div>
                    <form class="form-grid" method="post" action="{{ route('business.integrations.store', $integrationPage['provider']) }}">
                        @csrf
                        @foreach($integrationPage['fields'] as $field)
                            <div>
                                <label>{{ $field['label'] }}</label>
                                <input name="{{ $field['name'] }}" placeholder="{{ $field['placeholder'] }}" @required($field['name'] === 'restaurant_name')>
                                @if(isset($field['hint']))
                                    <div class="hint">{{ $field['hint'] }}</div>
                                @endif
                            </div>
                        @endforeach
                        <div class="toggle">
                            <label style="margin:0">Durum</label>
                            <input type="checkbox" name="status" value="1">
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn-ghost" data-close-integration-modal>Vazgeç</button>
                            <button type="submit" class="btn-success">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        @elseif(str_starts_with($page, 'ayarlar') || str_starts_with($page, 'entegrasyonlar') || str_starts_with($page, 'yardim') || $page === 'diger-entegrasyonlar' || $page === 'okc-pavo')
            <div class="section">
                <h3>Yapılandırma</h3>
                <div class="toolbar">
                    <input placeholder="API anahtarı / değer">
                    <select><option>Aktif</option><option>Pasif</option></select>
                    <button class="btn" type="button">Kaydet</button>
                </div>
                <p style="color:#64748b;margin:0">Bu bölüm seçilen ayar/entegrasyon menüsüne göre detaylandırılacak şekilde hazırlandı.</p>
            </div>
        @else
            <div class="placeholder">Bu bölüm için içerik yakında eklenecek.</div>
        @endif
        </div>
    </main>
</div>

<script>
    (function () {
        const contentEl = document.querySelector('.content');
        if (!contentEl) return;

        const customerStoreUrl = @json(route('business.customers.store'));
        const customerUpdateUrl = (id) => `{{ url('/isletme/musteriler') }}/${id}/guncelle`;

        const resetCustomerFormForCreate = () => {
            const form = contentEl.querySelector('#customer-form');
            if (!form) return;
            form.action = customerStoreUrl;
            form.reset();
            const hid = form.querySelector('#fld-customer-edit-id');
            if (hid) hid.value = '';
            const titleEl = contentEl.querySelector('#customer-modal-title');
            if (titleEl) titleEl.textContent = 'Müşteri ekle';
            const sub = contentEl.querySelector('#customer-form-submit');
            if (sub) sub.textContent = 'Kaydet';
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

        const syncManualOrderFields = () => {
            const pick = contentEl.querySelector('#mo-pick-customer');
            const name = contentEl.querySelector('#mo-name');
            const phone = contentEl.querySelector('#mo-phone');
            if (!pick || !name || !phone) return;
            name.required = !pick.value;
            phone.required = !pick.value;
        };

        contentEl.addEventListener('change', (event) => {
            if (event.target.id !== 'mo-pick-customer') return;
            const sel = event.target;
            const opt = sel.options[sel.selectedIndex];
            const name = contentEl.querySelector('#mo-name');
            const phone = contentEl.querySelector('#mo-phone');
            if (!name || !phone) return;
            if (!sel.value) {
                name.value = '';
                phone.value = '';
            } else {
                name.value = opt.dataset.name || '';
                phone.value = opt.dataset.phone || '';
            }
            syncManualOrderFields();
        });

        /**
         * İçerik AJAX ile değişince modaller yeniden oluşur; .content üzerinde delegation kullan.
         */
        contentEl.addEventListener('click', (event) => {
            const t = event.target;
            if (t.closest('[data-open-manual-order-modal]')) {
                contentEl.querySelector('#manual-order-modal')?.classList.add('open');
                queueMicrotask(() => syncManualOrderFields());
                return;
            }
            if (t.closest('[data-close-manual-order-modal]')) {
                contentEl.querySelector('#manual-order-modal')?.classList.remove('open');
                return;
            }
            if (t.id === 'manual-order-modal') {
                t.classList.remove('open');
                return;
            }

            if (t.closest('[data-open-customer-modal]')) {
                resetCustomerFormForCreate();
                contentEl.querySelector('#customer-modal')?.classList.add('open');
                return;
            }
            const editCust = t.closest('[data-edit-customer]');
            if (editCust) {
                const form = contentEl.querySelector('#customer-form');
                const modal = contentEl.querySelector('#customer-modal');
                if (form && modal) {
                    const id = editCust.dataset.id;
                    form.action = customerUpdateUrl(id);
                    const hid = form.querySelector('#fld-customer-edit-id');
                    if (hid) hid.value = id;
                    const setVal = (name, val) => {
                        const el = form.querySelector(`[name="${name}"]`);
                        if (el) el.value = val ?? '';
                    };
                    setVal('name', editCust.dataset.name);
                    setVal('email', editCust.dataset.email);
                    setVal('phone_1', editCust.dataset.phone);
                    setVal('phone_2', editCust.dataset.phone2);
                    setVal('note', editCust.dataset.note);
                    setVal('addr_title', editCust.dataset.addrTitle);
                    setVal('addr_line', editCust.dataset.addrLine);
                    setVal('addr_district', editCust.dataset.addrDistrict);
                    setVal('addr_city', editCust.dataset.addrCity);
                    setVal('addr_directions', editCust.dataset.addrDirections);
                    const titleEl = contentEl.querySelector('#customer-modal-title');
                    if (titleEl) titleEl.textContent = 'Müşteri düzenle';
                    const sub = contentEl.querySelector('#customer-form-submit');
                    if (sub) sub.textContent = 'Güncelle';
                    modal.classList.add('open');
                }
                return;
            }
            if (t.closest('[data-close-customer-modal]')) {
                contentEl.querySelector('#customer-modal')?.classList.remove('open');
                return;
            }
            if (t.id === 'customer-modal') {
                t.classList.remove('open');
                return;
            }

            if (t.closest('[data-open-integration-modal]')) {
                contentEl.querySelector('#integration-modal')?.classList.add('open');
                return;
            }
            if (t.closest('[data-close-integration-modal]')) {
                contentEl.querySelector('#integration-modal')?.classList.remove('open');
                return;
            }
            if (t.id === 'integration-modal') {
                t.classList.remove('open');
            }
        });

        queueMicrotask(() => syncManualOrderFields());
    })();
</script>
</body>
</html>

