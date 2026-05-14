# Proje dokümantasyon indeksi

Bu klasör, mimari/strateji özeti, yol haritası ve epik bazlı **tasarım notlarını** içerir. Köke göre yollar `docs/...` şeklindedir.

## Strateji ve yol haritası

| Belge | İçerik |
|--------|--------|
| [PLATFORM_MIMARI_VE_REKABET.md](PLATFORM_MIMARI_VE_REKABET.md) | Mevcut mimari, rakip eksenleri, SaaS önerileri, TR stratejisi, 20 özellik listesi |
| [ROADMAP_EPICS.md](ROADMAP_EPICS.md) | Faz 0–3 epik/issue listesi ve kabul kriterleri özetleri |

## Epik tasarım notları

### Faz 1 — Operasyon çekirdeği

| Epik | Belge |
|------|--------|
| EPIC-10A Canlı operasyon / harita | [EPIC_10A_OPERASYON_EKRANI.md](EPIC_10A_OPERASYON_EKRANI.md) |
| EPIC-10B Otomatik atama (dispatch v1) | [EPIC_10B_DISPATCH_V1.md](EPIC_10B_DISPATCH_V1.md) |
| EPIC-10C Müşteri takip linki | [EPIC_10C_MUSTERI_TAKIP.md](EPIC_10C_MUSTERI_TAKIP.md) |

### Faz 2 — Entegrasyon

| Epik | Belge |
|------|--------|
| EPIC-20A Giden webhook | [EPIC_20A_WEBHOOKS.md](EPIC_20A_WEBHOOKS.md) |
| EPIC-20B Agregatör / POS pilot | [EPIC_20B_AGGREGATOR_PILOT.md](EPIC_20B_AGGREGATOR_PILOT.md) |
| Trendyol Yemek (TGO) kurulum rehberi | [TRENDYOL_GO_YEMEK_ENTEGRASYON.md](TRENDYOL_GO_YEMEK_ENTEGRASYON.md) |

### Faz 3 — SaaS ve ölçek

| Epik | Belge |
|------|--------|
| EPIC-30A Abonelik / faturalama | [EPIC_30A_BILLING_SAAS.md](EPIC_30A_BILLING_SAAS.md) |
| EPIC-30B BI / export | [EPIC_30B_BI_EXPORT.md](EPIC_30B_BI_EXPORT.md) |

## Depo kökündeki ilgili notlar

| Belge | İçerik |
|--------|--------|
| [REFERANS_SITE_NOTLARI.md](../REFERANS_SITE_NOTLARI.md) | Loji referans menü ve sayfa pattern özeti |

## Kiracılık / rota notu

Çok kiracılı mağaza: `ResolveFirmFromDomain` + `firm_id`. Açıklama: [routes/tenant.php](../routes/tenant.php) (placeholder, `bootstrap/app.php` tarafından yüklenmez).

## Bakım

- Yeni epik tasarım dosyası eklendiğinde bu indeksteki tabloları güncelleyin.
- Konum API + Redis GEO: kök [.env.example](../.env.example) içinde `COURIER_GEO_REDIS`.
