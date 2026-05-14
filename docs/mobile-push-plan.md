## Mobil Push Bildirim Planı (MVP sonrası)

Bu doküman Flutter tek uygulama için **FCM + APNs** push tasarımını özetler.

### 1) Hedef bildirimler

- **Kurye**
  - Yeni sipariş atandı
  - Sipariş iptal edildi / değişti
- **Restoran (firma)**
  - Yeni sipariş (pazar yeri / mağaza)
  - Sipariş durumu güncellendi
- **Kurye şirketi admin**
  - Restoran “Kurye çağır” sinyali (ready + courier requested)

### 2) Teknik yaklaşım

- Mobil: Firebase SDK ile cihaz `fcm_token` alır.
- Backend:
  - `device_tokens` tablosu (user_id, platform, token, last_seen_at)
  - `POST /api/v1/devices/register` ile token kaydı (auth:sanctum)
  - Bildirim gönderimi kuyruk üzerinden (`QUEUE_CONNECTION=redis` önerilir)

### 3) Mesaj formatı (öneri)

- `type`: `courier_assigned` | `order_cancelled` | `new_order` | `courier_requested`
- `entity_id`: `order_id`
- `title/body`: kullanıcıya görünen metin
- `data`: deep link parametreleri (örn. `screen=order&order_id=123`)

### 4) Güvenlik / Performans

- Token’ları user’a bağlı tut.
- Aynı token tekrar kayıt olursa upsert.
- Rate limit: cihaz register endpoint’ine IP + user bazlı limit.
- Bildirim gönderiminde retry + dead-letter yaklaşımı.

