## Mobil uygulama + harita maliyeti notları

Tarih: 2026-04-25

### Hedef
- Android + iOS birlikte (tek kod tabanı tercih).
- En kritik uygulama: **Kurye uygulaması** (MVP), sonra restoran/firma mobil.

### Teknoloji önerisi
- **Flutter**: tek kod tabanı, harita/konum/push tarafı güçlü, MVP hızlı.
- Alternatif: React Native (konum/push tarafında daha çok cihaz-özel ayar gerekebilir).
- PWA: kurye için (arka plan konum + push) genelde yetersiz.

### Kurye uygulaması MVP kapsamı
- Login (token saklama)
- Aktif görev listesi (courier_assigned / picked_up / on_the_way)
- Sipariş detayı (adres, müşteri, not, tutarlar)
- Durum güncelleme butonları (picked_up → on_the_way → delivered)
- Navigasyon deeplink (Google/Apple Maps’e aç)
- Konum gönderimi:
  - Aktif teslim varken sık (örn 5–15sn), yokken seyrek (örn 60sn)
  - Offline buffer + retry
  - Pil dostu, doğruluk/yaş kontrolü
- Push bildirim (yeni atama/iptal/değişim)

### Harita maliyeti: temel gerçek
- Maliyet sipariş adedinden çok:
  - **Harita görüntüleme (map load) sayısı**
  - **Rota/ETA (Directions/Routes) çağrısı sayısı**
  - Ekranın “sürekli açık” kalması + yeniden yüklemeler

### Risk
- 4.000 restoran × 100 sipariş/gün = 400.000 sipariş/gün (aylık ~12M sipariş)
- Kullanıcıların haritaya aynı anda bakma sayısı belirsiz → usage-based servislerde fatura sürprizi riski büyük.

### Önerilen maliyet-kontrollü mimari
- **Harita görüntüleme**: OSM tabanlı
  - Mobil: `flutter_map`
  - Web: Leaflet / MapLibre
- **Canlı kurye konumu**: kendi API + cache/Redis (sadece koordinat akışı)
- **Rota/ETA**:
  - MVP: rota çizmeden, sadece “Google/Apple Maps’te aç” deeplink
  - İleride gerekiyorsa: **OSRM** gibi routing’i self-host (sunucu maliyeti; usage-based sürpriz yok)

### Google/Mapbox kullanım notu
- Google/Mapbox (SDK + Directions) yüksek kullanımda hızlı büyüyen maliyet çıkarabilir.
- Google’ı faturasız kullanma yaklaşımı: uygulama içinde Directions API çağırmak yerine
  **navigasyonu dış uygulamada açmak** (deeplink).

### Sonraki adım (mobil planlama yapılırken)
- Konum gönderim aralığı + “aktif teslim” tespit stratejisi
- Push token yönetimi ve bildirim ayarları (sessiz saatler)
- Realtime mimarisi (polling → SSE/websocket)
- Harita altyapısı: tile cache/CDN, OSRM hosting, ölçekleme

