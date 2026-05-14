# Roadmap (epic/issue listesi)

Tüm dokümanların indeksi: [README.md](README.md).

Bu belge, [PLATFORM_MIMARI_VE_REKABET.md](PLATFORM_MIMARI_VE_REKABET.md) strateji özetini **uygulanabilir epiklere** böler.

Kaynak rakip navigasyon/pattern notları: [REFERANS_SITE_NOTLARI.md](../REFERANS_SITE_NOTLARI.md)

---

## Faz 0 (1–2 hafta) — Temizlik ve altyapı kararları

### EPIC-00A — Tenancy yaklaşımını netleştir

- **Amaç:** Şu anki modelin (domain + `firm_id`) resmileştirilmesi veya gerçek tenancy’ye geçiş.
- **Mevcut durum:** `routes/tenant.php` artık placeholder; `bootstrap/app.php` tenant rotalarını yüklemiyor.
- **Kabul kriterleri:**
  - `README`/`docs` içinde net karar: *shared DB* mi, *schema-per-tenant* mı, *DB-per-tenant* mı?
  - Her request’te firma bağlamı (`FirmContext`) ve veri scope kuralları yazılı.

### EPIC-00B — API sözleşmesi ve sürümleme

- **Amaç:** Entegrasyonlar başlamadan API kırılganlığını azaltmak.
- **Kabul kriterleri:**
  - `/api/v1` için sürümleme politikası ve değişiklik süreci dokümante.
  - Rate limit, auth ve hata formatı standardı.

### EPIC-00C — Ödeme gateway seçimi (pilot)

- **Amaç:** `NullPaymentGateway` yerine bir gerçek sağlayıcı pilotu.
- **Kabul kriterleri:**
  - En az 1 sağlayıcıyla sandbox akışı (refund dahil) belirlenmiş.
  - Başarısız ödemelerde idempotency ve retry stratejisi.

---

## Faz 1 (2–3 ay) — Operasyon çekirdeği

### EPIC-10A — Canlı operasyon ekranı (firma)

- **Tasarım dokümanı:** [EPIC_10A_OPERASYON_EKRANI.md](EPIC_10A_OPERASYON_EKRANI.md)
- **Amaç:** Harita + sipariş havuzu + kurye konumları ile tek ekranda yönetim.
- **Kod tabanı ipuçları:**
  - Kurye konumu API: `routes/api.php` → `CourierLocationApiController@store`.
  - DB model: `app/Modules/Couriers/Models/CourierLocation.php`.
- **Kabul kriterleri:**
  - Firma panelinde “Harita/Operasyon” ekranı.
  - Kurye pinleri + aktif siparişler (filtrelenebilir).
  - Manuel yeniden atama (order → courier).

### EPIC-10B — Skor tabanlı otomatik atama (dispatch v1)

- **Tasarım dokümanı:** [EPIC_10B_DISPATCH_V1.md](EPIC_10B_DISPATCH_V1.md)
- **Amaç:** Rakiplerden (Loji/JaviKurye) beklenen “otomatik atama”yı minimum viable şekilde sunmak.
- **V1 skor parametreleri:** mesafe, kurye aktif sipariş sayısı, bölge uyumu, kurye puanı.
- **Kabul kriterleri:**
  - Yeni siparişlerde (veya havuzda) “Otomatik ata” çalıştırılabilir.
  - Atama kararı loglanır (audit) ve geri alınabilir.

### EPIC-10C — Müşteri teslim takip linki (ETA)

- **Tasarım dokümanı:** [EPIC_10C_MUSTERI_TAKIP.md](EPIC_10C_MUSTERI_TAKIP.md)
- **Amaç:** Müşteriye paylaşılabilir takip sayfası.
- **Kabul kriterleri:**
  - Sipariş için share token’lı link.
  - ETA hesaplaması (başta basit: ortalama hız + kalan mesafe).
  - KVKK/gizlilik: konum hassasiyetini düşürme/kapama.

---

## Faz 2 (3–6 ay) — Entegrasyon hub

### EPIC-20A — Webhook sistemi (outgoing)

- **Tasarım dokümanı:** [EPIC_20A_WEBHOOKS.md](EPIC_20A_WEBHOOKS.md)
- **Amaç:** Firmaların/partnerlerin sipariş durum değişimlerini dinlemesi.
- **Kabul kriterleri:**
  - Event: order.created, order.status_changed, order.cancelled.
  - Retry + imza (HMAC) + teslim logları.

### EPIC-20B — Agregatör/POS entegrasyonu (1 pilot)

- **Tasarım dokümanı:** [EPIC_20B_AGGREGATOR_PILOT.md](EPIC_20B_AGGREGATOR_PILOT.md)
- **Amaç:** Yemek agregatörlerinden sipariş çekmek veya durum yazmak.
- **Kabul kriterleri:**
  - En az 1 entegrasyon uçtan uca (order ingest + status sync).
  - Idempotent ingest + mapping tabloları.

---

## Faz 3 (6–12 ay) — SaaS ve ölçek

### EPIC-30A — Abonelik ve faturalama

- **Tasarım dokümanı:** [EPIC_30A_BILLING_SAAS.md](EPIC_30A_BILLING_SAAS.md)
- **Amaç:** Paketler, kullanım metrikleri, tahsilat.
- **Kabul kriterleri:**
  - Plan/paket modeli (kurye sayısı, sipariş hacmi vb.).
  - Firma bazlı limitler ve aşımlarda davranış.

### EPIC-30B — BI / rapor katmanı

- **Tasarım dokümanı:** [EPIC_30B_BI_EXPORT.md](EPIC_30B_BI_EXPORT.md)
- **Amaç:** KPI, zaman serisi raporlar, export.
- **Kabul kriterleri:**
  - “Teslim edilen / iptal edilen” raporları; tarih aralığı; KPI kartları.
  - Excel/PDF export.

---

## Rakip menü/pattern uyumu (Loji referansı)

Aşağıdaki yapı [REFERANS_SITE_NOTLARI.md](../REFERANS_SITE_NOTLARI.md) ile uyumludur.

- **Güncel Durum / Siparişler:** sipariş havuzu + arama + kolon seçimi + sayfalama
- **Teslim Edilenler / İptal Edilenler:** tarih aralığı + filtreler + KPI kartları
- **İşletmeler / Kuryeler:** arama + kolonlar + satır aksiyonları
- **Harita:** operasyon ekranı
- **Cari Hesap / Kontör:** SaaS faturalama ile bağlanmalı

---

## Teknik borç / dikkat notları

- `resources/views/company/panel.blade.php` ve `resources/views/business/panel.blade.php` içinde kapsamlı menü/panel UI’ları var fakat `routes/web.php` içinde `company.*` / `business.*` rotaları görünmüyor. 
  - **Seçenek A:** Bu panelleri ürüne dahil etmek için route+controller tasarla.
  - **Seçenek B:** Ürün kapsamı dışıysa kaldır/ayır (bakım maliyeti).

- **Redis GEO (kurye konumu):** `CourierGeoLocatorInterface` + `RedisGeoCourierLocator` / `NullCourierGeoLocator`; `COURIER_GEO_REDIS=true` ile açılır (`config/courier.php`). `POST api/v1/courier/location` DB’ye yazdıktan sonra indeksler.

