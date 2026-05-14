## Amaç

Bu doküman, **Google Maps maliyetine girmeden**; kurye canlı konum + yakın kurye önerisi + (opsiyonel) ETA/routing altyapısını **kendi sunucunuzda** çalıştırmak için gereken minimum ayarları toplar.

> Not: Plan dosyasını değiştirmeden, uygulanabilir “operasyon checklist” burada tutulur.

## 1) Redis GEO’yu açma (yakın kurye + operasyon haritası)

### Gerekli env

`.env` içine:

- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis` (async GEOADD kullanacaksanız)
- `COURIER_GEO_REDIS=true`

Opsiyonel:

- `COURIER_GEO_REDIS_ASYNC=true` (yükte önerilir)
- `COURIER_GEO_REDIS_QUEUE=geo`

Varsayılanlar `config/courier.php` içinden okunur.

### Redis bağlantısı

Varsayılan bağlantı Laravel’in `redis` ayarlarıdır:

- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`
- `REDIS_DB/REDIS_CACHE_DB/REDIS_QUEUE_DB`

Eğer GEO’yu ayrı bir Redis örneğine taşımak isterseniz:

- `COURIER_GEO_REDIS_CONNECTION=default` yerine `courier_geo` gibi bir connection adı verin
- `config/database.php` içinde `redis.connections.courier_geo` ekleyin

> Bu projede GEO anahtarı firma bazlıdır: `{COURIER_GEO_REDIS_KEY}:{firm_id}`.

### Çalıştığını doğrulama

- Operasyon ekranında uyarı kalkmalı (Redis GEO açık olunca).
- Konum akışı: `POST /api/v1/courier/location` çağrıları MySQL’e yazar, GEO açıksa Redis’e de indeksler.

## 2) Yük altında strateji (GEOADD async)

Önerilen pratik:

- **Başlangıç / düşük yük**: `COURIER_GEO_REDIS_ASYNC=false`
- **Bin+ kurye / 10 sn konum**: `COURIER_GEO_REDIS_ASYNC=true` ve ayrı queue worker

Queue worker (Linux):

- `php artisan queue:work --queue=geo,default --sleep=1 --tries=3`

Windows (local dev): `queue:work` çalışır; `horizon` Windows’ta sınırlıdır.

Supervisor örneği:

- `deploy/supervisor-queue-worker.conf.example`

## 3) Operasyon ekranı — tile bağımlılığını azaltma

`config/map.php` ile tile kaynağı konfigüre edilir:

- `MAP_TILES_URL`
- `MAP_TILES_ATTRIBUTION`

Üretimde en az riskli yol:

- Dış OSM tile’ı doğrudan istemciye açmak yerine
- Kendi domain’inizde **Nginx reverse-proxy + disk cache** ile servis etmek

Örnek Nginx config:

- `deploy/nginx-tiles-proxy.conf.example`

Bu şekilde:

- Aynı tile tekrarları diskte cache’lenir
- Dış servis “best effort” olur, maliyet/kota riski düşer

## 4) Routing/ETA (opsiyonel): OSRM

ETA için iki aşamalı akış önerilir:

1) Redis GEO ile aday kuryeleri (ör. ilk 50) daralt
2) OSRM ile gerçek yol süresine göre ETA hesapla

OSRM’i docker ile ayrı servis olarak koşup, uygulamadan HTTP ile çağırın.

### Docker Compose iskeleti

- `docker-compose.location.yml` (OSRM servisi `profiles: ["osrm"]` ile gelir)

Çalıştırma:

- Redis + app dışı servisler: `docker compose -f docker-compose.location.yml up -d`
- OSRM’i de açmak için: `docker compose -f docker-compose.location.yml --profile osrm up -d`

### OSRM veri hazırlığı (tek seferlik)

`./storage/osrm` altında `turkey.osm.pbf` (örn. Geofabrik) indirdikten sonra:

- `osrm-extract -p /opt/car.lua /data/turkey.osm.pbf`
- `osrm-partition /data/turkey.osrm`
- `osrm-customize /data/turkey.osrm`

Sonra `osrm-routed` ile servis başlatılır.

## 5) Geocoding (opsiyonel)

Adres arama/normalize ihtiyacı doğduğunda:

- Nominatim veya Pelias

İlk fazda şart değil; koordinat toplama mobil uygulama üzerinden ilerleyebilir.

