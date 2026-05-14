# Yemeksepeti Bridge Test Rehberi

Bu rehber, eklenen 3 komutu (Outlet, Catalog, Promotions) hizli test etmek icindir.

## 1) Panelde entegrasyon alanlarini doldur

`Restoran Paneli > Pazar yeri API > Yemeksepeti`:

- `API Base URL` (ornek: `https://yemeksepeti.partner.deliveryhero.io`)
- `Chain ID`
- `Vendor ID`
- `Client ID`
- `Client Secret`

Not: Client bilgileriniz kayitliyken UI'da tekrar gosterilmez.

## 2) Outlet status komutu

```bash
php artisan kurye:yemeksepeti-outlet-status --restaurant=1 --status=OPEN
php artisan kurye:yemeksepeti-outlet-status --restaurant=1 --status=CLOSED --closed-reason="Yogunluk" --closed-until="2026-04-29T18:00:00Z"
php artisan kurye:yemeksepeti-outlet-status --restaurant=1 --check
```

## 3) Catalog async job komutu

Ornek payload:
`docs/examples/yemeksepeti-catalog-payload.sample.json`

Not: payload icinde mutlaka `products` array’i olmalidir.

```bash
php artisan kurye:yemeksepeti-catalog-bridge --restaurant=1 --payload-file="docs/examples/yemeksepeti-catalog-payload.sample.json"
```

Donen cevapta `job_id` benzeri alan alirsaniz status kontrol edin:

```bash
php artisan kurye:yemeksepeti-catalog-bridge --restaurant=1 --job-id=JOB_ID_HERE
```

## 4) Promotions async job komutu

Ornek payload:
`docs/examples/yemeksepeti-promotions-payload.sample.json`

Not: payload icinde `vendors` (en az bir vendor id) alanini vermeniz gerekir.

```bash
php artisan kurye:yemeksepeti-promotions-bridge --restaurant=1 --payload-file="docs/examples/yemeksepeti-promotions-payload.sample.json"
```

Status kontrol:

```bash
php artisan kurye:yemeksepeti-promotions-bridge --restaurant=1 --job-id=JOB_ID_HERE
```

## 5) Sik hatalar

- `client_id ve client_secret gerekli.`  
  Panelde kaydedilmemis veya override verilmemis.
- `Payload dosyasi bulunamadi`  
  Yol yanlis ya da terminal calisma dizini farkli.
- `404` / `405` endpoint hatalari  
  Dokumana gore path alanlarini panelde duzeltin.
- `401` / `403`  
  Partner kimlik bilgileri yanlis veya yetki yok.
