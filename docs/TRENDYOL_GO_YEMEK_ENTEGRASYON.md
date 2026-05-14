## Trendyol Go (TGO) Yemek entegrasyonu

Bu projede Trendyol Yemek entegrasyonu **TGO (Trendyol Go) “Webhook Meal Integration”** dokümanına göre yapılır.

### 1) Projedeki uç (webhook endpoint)

- **Webhook URL**: `POST /api/v1/integrations/trendyol_yemek/webhook`
- **Zorunlu header**: `X-Integration-Token`
  - Token, restoran panelindeki “Entegrasyonlar” ekranından otomatik üretilir ve bağlantı kaydıyla eşleştirilir.

Not: Bu endpoint bu projede geneldir (`/api/v1/integrations/{provider}/webhook`). `trendyol_yemek` için gelen TGO payload’ı otomatik normalize edilip siparişe dönüştürülür.

### 2) Panel ayarları (restoran)

`/restoran/entegrasyon` → **Trendyol Yemek (Trendyol Go)**

- **TGO ortamı**: prod `https://api.tgoapis.com` (stage `https://stageapi.tgoapis.com`)
- **Integrator name**
- **Executor user e-mail**
- **Seller ID (Cari ID)**
- **API Key / API Secret**
- **Access token (Bearer)** (isteğe bağlı; komutlar create/refresh ile kaydedebilir)

Bu bilgiler:
- `settings_json`: integratorName/executor/api_base_url ve token gibi alanlar
- `credentials_encrypted`: seller/apiKey/apiSecret/accessToken gibi gizli alanlar

### 3) Kurulum komutu (önerilen yol)

Kurulum/bağlantı işlemlerini kolaylaştırmak için bir yardımcı komut var:

- `php artisan kurye:tgo-setup --restaurant=RESTAURANT_ID --env=prod --create-integrator`
- `php artisan kurye:tgo-setup --restaurant=RESTAURANT_ID --env=prod --add-seller`
- `php artisan kurye:tgo-setup --restaurant=RESTAURANT_ID --env=prod --enable`
- `php artisan kurye:tgo-setup --restaurant=RESTAURANT_ID --env=prod --test-order`

Token yenileme:

- `php artisan kurye:tgo-setup --restaurant=RESTAURANT_ID --env=prod --refresh-token`

Önemli:
- TGO’nun webhook atabilmesi için uygulamanın URL’si internetten erişilebilir olmalıdır.
- Lokal geliştirmede `APP_URL` + reverse proxy/ngrok gibi bir çözüm gerekir.

### 4) Sipariş oluşturma (TGO → Kurye)

TGO’nun `created` event’i geldiğinde sistem şu alanları normalize eder:

- **external_order_id**: `orderCode`
- **items[]**: `lines[].items[]` içinden `productId` → `external_sku`, `quantity`
- **customer_name / customer_phone**
- **delivery_address** + mümkünse **lat/lng**
- **notes**: `customerNote`
- **payment_method**: `PAY_WITH_CARD` → `online`, diğerleri → `cash_on_delivery`

Ürün eşlemesi:
- Panelde **Ürün eşlemesi** alanında `external_sku = productId` olacak şekilde eşleme yapın.

### 5) Durum güncelleme (TGO → Kurye)

TGO event’leri iç sipariş durumuna map edilir:

- `shipped` → `on_the_way`
- `delivered` → `delivered`
- `cancelled` / `unsupplied` → `cancelled`

Sağlamlık kuralları:
- Sipariş `delivered` veya `cancelled` ise, sonraki status event’leri **geri düşürmez** (skip edilir).
- Status geriye giden (out-of-order) event’ler **skip** edilir.

