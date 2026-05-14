# Yemeksepeti Outlets (OPEN/CLOSED) - Testing / Troubleshooting / FAQ

Bu doküman, sistemden Yemeksepeti Partner API’ye **outlet durumunu (OPEN/CLOSED)** göndermek için eklenen bridge’leri test etmeyi anlatır.

## Durum gönderme (Testing)

1) Panelden entegrasyonu tamamlayın:

- `Restoran Paneli > Pazar yeri API > Yemeksepeti`
  - `API Base URL`, `Chain ID`, `Vendor ID`, `Client ID`, `Client Secret`

2) Komut:

```bash
php artisan kurye:yemeksepeti-outlet-status --restaurant=1 --status=OPEN
php artisan kurye:yemeksepeti-outlet-status --restaurant=1 --status=CLOSED --closed-reason="Yogunluk" --closed-until="2026-04-29T18:00:00Z"
php artisan kurye:yemeksepeti-outlet-status --restaurant=1 --check
```

## Sık hata / Troubleshooting

- `Yemeksepeti entegrasyon baglantisi bulunamadi.`
  - İlgili `restaurant_id` için `provider=yemeksepeti` kayıtlı entegrasyon yok.
- `chain_id, vendor_id, client_id, client_secret degerleri gerekli.`
  - Panelden eksik alanlar var veya `settings_json` / şifreli credentials doldurulmadı.
- `Yemeksepeti istegi basarisiz: ...`
  - Partner endpoint path’leri veya kimlik bilgileri yanlış olabilir.
  - Hata mesajında dönen HTTP koduna göre (401/403/404) alanları kontrol edin.

## FAQ

- `--check` ne yapar?
  - Partner API’den mevcut outlet durumunu anlık olarak çekmeye çalışır.
- “Kapalı” gönderirken `closed-reason` şart mı?
  - Partner dokümanına göre değişebilir. Bu bridge alanı opsiyonel gönderir; isterseniz zorunluysa panelde / komutta doldurun.

