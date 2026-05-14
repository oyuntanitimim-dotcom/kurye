# Yemeksepeti Entegrasyon Doküman Uyumluluk Özeti (Tamam/Eksik)

Not: Bu proje dokümanlarına göre “tamam” / “eksik” değerlendirmesi, repo içinde fiilen bulunan bridge/akışlara göre yapılır (partner tarafındaki payload şeması doğrulaması bizde otomatik doğrulanmıyor).

## Outlets (OPEN/CLOSED)

- `OPEN/CLOSED` gönderme: `Tamam`
  - `kurye:yemeksepeti-outlet-status` komutu ile `PUT /v2/chains/{chain_id}/vendors/{vendor_id}/status` akışı.
- Güncel durum okuma: `Tamam`
  - `--check` ile `GET /v2/chains/{chain_id}/vendors/{vendor_id}/status`.
- Panelden endpoint/kimlik bilgisi yönetimi: `Tamam`
  - `API Base URL`, `Chain ID`, `Vendor ID`, `Client ID/Secret` + gerekirse path alanları.
- Otomatik aralıklarla sync/scheduler: `Eksik`
  - Şu an manuel (komut) tetikleme var.

## Catalog (Async Job)

- Async job start: `Tamam`
  - `kurye:yemeksepeti-catalog-bridge --payload-file=...` ile.
- Async job status: `Tamam`
  - `--job-id=...` ile status kontrol.
- Payload şema doğrulaması (bizde): `Eksik`
  - Kod tarafı “JSON object/array” doğruluyor; alanların zorunluluğu partner spesifikasyonuna bağlı.

## Promotions (Async Job)

- Async job start: `Tamam`
  - `kurye:yemeksepeti-promotions-bridge --payload-file=...` ile.
- Async job status: `Tamam`
  - `--job-id=...` ile.
- Payload şema doğrulaması (bizde): `Eksik`
  - Aynı şekilde “JSON geçerliliği” seviyesiyle sınırlı.

## Testing / Troubleshooting / FAQ

- Outlets test guide: `Tamam`
  - `docs/YEMEKSEPETI_OUTLETS_TESTING_TROUBLESHOOTING_FAQ.md`
- Catalog test guide: `Tamam`
  - `docs/YEMEKSEPETI_CATALOG_TESTING_TROUBLESHOOTING_FAQ.md`
- Promotions test guide: `Tamam`
  - `docs/YEMEKSEPETI_PROMOTIONS_TESTING_TROUBLESHOOTING_FAQ.md`
- Örnek payload: `Tamam`
  - `docs/examples/*sample.json`

