# Yemeksepeti Promotions - Testing / Troubleshooting / FAQ

Bu doküman, Yemeksepeti Partner API’ye **Promotions async job** başlatıp job durumunu kontrol etmek için eklenen bridge’leri anlatır.

## Async job akışı (Testing)

1) Panelden entegrasyonu tamamlayın:

- `Restoran Paneli > Pazar yeri API > Yemeksepeti`
  - `API Base URL`, `Chain ID`, `Vendor ID`, `Client ID`, `Client Secret`
  - `Promotions Start Path`, `Promotions Status Path`

2) Payload örneği:

- `docs/examples/yemeksepeti-promotions-payload.sample.json`

3) Job start:

```bash
php artisan kurye:yemeksepeti-promotions-bridge --restaurant=1 --payload-file="docs/examples/yemeksepeti-promotions-payload.sample.json"
```

4) Job status kontrol:

```bash
php artisan kurye:yemeksepeti-promotions-bridge --restaurant=1 --job-id=JOB_ID_HERE
```

## Sık hata / Troubleshooting

- `Payload gecerli JSON object/array olmali`
  - Payload dosyasını JSON formatında verin.
- `404` / `405`
  - Start/Status path değerlerini partner dokümanına göre düzeltin.
- `401` / `403`
  - Yetki / kimlik bilgileri yanlış olabilir.

## FAQ

- “job-id” değeri nereden geliyor?
  - Job start response’unun içinde partnerin döndürdüğü `job_id` (veya benzeri alan) değeri kullanılır.

