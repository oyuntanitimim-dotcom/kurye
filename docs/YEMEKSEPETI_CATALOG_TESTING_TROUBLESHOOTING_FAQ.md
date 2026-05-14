# Yemeksepeti Catalog - Testing / Troubleshooting / FAQ

Bu doküman, Yemeksepeti Partner API’ye **Catalog async job** başlatıp job durumunu kontrol etmek için eklenen bridge’leri anlatır.

## Async job akışı (Testing)

1) Panelden entegrasyonu tamamlayın:

- `Restoran Paneli > Pazar yeri API > Yemeksepeti`
  - `API Base URL`, `Chain ID`, `Vendor ID`, `Client ID`, `Client Secret`
  - `Catalog Start Path`, `Catalog Status Path`

2) Payload örneği:

- `docs/examples/yemeksepeti-catalog-payload.sample.json`

3) Job start:

```bash
php artisan kurye:yemeksepeti-catalog-bridge --restaurant=1 --payload-file="docs/examples/yemeksepeti-catalog-payload.sample.json"
```

4) Job status kontrol:

`job_id` (veya benzeri) değerini response’tan alın:

```bash
php artisan kurye:yemeksepeti-catalog-bridge --restaurant=1 --job-id=JOB_ID_HERE
```

## Sık hata / Troubleshooting

- `Payload dosyasi bulunamadi`
  - Komutta `--payload-file` yolunu doğru verin.
- `Payload gecerli JSON object/array olmali`
  - Dosya JSON değil / boş / BOM / invalid karakter içerebilir.
- `404` / `405`
  - `Catalog Start Path` / `Catalog Status Path` dokümana göre yanlış olabilir. Panelden düzeltin.
- `401` / `403`
  - `Client ID/Client Secret` veya yetki yetersiz olabilir.

## FAQ

- Bridge neden “path” ile parametrik?
  - Partner dokümanları endpoint path/versiyonlarını değiştirebiliyor. Biz de panelden path override ile esnek hale getirdik.

