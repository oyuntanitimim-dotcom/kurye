<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Konum API — dakikada istek (kurye başına, auth:sanctum)
    |--------------------------------------------------------------------------
    |
    | Çok sayıda kurye aynı anda çalışırken mobil uygulama GPS aralığına göre
    | ayarlayın (ör. 5–15 sn aralık ≈ 4–12 istek/dk). Üretimde Redis tabanlı
    | throttle (CACHE_STORE=redis) kullanın. Tüm API için genel limit routes/api.php içindedir.
    |
    */
    'location_posts_per_minute' => max(30, (int) env('COURIER_LOCATION_POSTS_PER_MINUTE', 180)),

    /*
    |--------------------------------------------------------------------------
    | Kurye konumu — Redis GEO indeksi
    |--------------------------------------------------------------------------
    |
    | true iken POST api/v1/courier/location sonrası GEOADD çalışır (yakın kurye /
    | operasyon ekranı için). Redis yoksa veya hata olursa log yazılır; DB kaydı yine de tutulur.
    | geo_redis_key değeri önek olarak kullanılır; gerçek anahtar: `{prefix}:{firm_id}`.
    |
    */
    'geo_redis_enabled' => filter_var(env('COURIER_GEO_REDIS', false), FILTER_VALIDATE_BOOLEAN),

    'geo_redis_key' => env('COURIER_GEO_REDIS_KEY', 'kurye:couriers:geo'),

    'geo_redis_connection' => env('COURIER_GEO_REDIS_CONNECTION', 'default'),

    /*
    | true iken konum API Redis GEOADD işini kuyruğa atar (QUEUE_CONNECTION=redis + worker gerekir).
    */
    'geo_redis_async' => filter_var(env('COURIER_GEO_REDIS_ASYNC', false), FILTER_VALIDATE_BOOLEAN),

    'geo_redis_queue' => env('COURIER_GEO_REDIS_QUEUE', 'geo'),

    /*
    | GEO key TTL (seconds)
    |
    | Redis GEO uses a single key per firm. We can set a TTL on that key so
    | inactive firms' GEO sets are cleaned up automatically. This is not a
    | per-courier TTL; stale couriers are filtered via DB `courier_locations.updated_at`.
    */
    'geo_redis_key_ttl_seconds' => max(0, (int) env('COURIER_GEO_REDIS_KEY_TTL_SECONDS', 172800)), // 2 days

    /*
    | Otomatik atama job’u (AutoDispatchOrderJob) için Horizon kuyruk adı.
    */
    'dispatch_queue' => env('QUEUE_DISPATCH', 'dispatch'),

    /*
    | Otomatik atama: Redis GEO açıkken önce restorana yakın kurye id'leri çekilir (DB'de tüm aktif listesi yerine).
    | Aday yoksa veya hepsi elenirse tam liste ile yeniden denenir.
    */
    'dispatch_geo_prefilter_radius_km' => max(5.0, (float) env('DISPATCH_GEO_PREFILTER_RADIUS_KM', 35.0)),

    'dispatch_geo_prefilter_limit' => max(20, (int) env('DISPATCH_GEO_PREFILTER_LIMIT', 120)),

    'operations_snapshot_order_limit' => (int) env('COURIER_OPERATIONS_SNAPSHOT_ORDERS', 80),

    'operations_near_radius_km' => (float) env('COURIER_OPERATIONS_NEAR_KM', 5),

    'operations_near_limit' => (int) env('COURIER_OPERATIONS_NEAR_LIMIT', 8),

    /*
    |--------------------------------------------------------------------------
    | Otomatik atama (EPIC-10B) — firma ayarları ile override edilebilir
    |--------------------------------------------------------------------------
    */
    'dispatch_weight_distance' => (float) env('DISPATCH_WEIGHT_DISTANCE', 1.0),

    'dispatch_weight_active_orders' => (float) env('DISPATCH_WEIGHT_ACTIVE_ORDERS', 2.0),

    'dispatch_weight_stale' => (float) env('DISPATCH_WEIGHT_STALE', 0.15),

    'dispatch_location_max_age_minutes' => (int) env('DISPATCH_LOCATION_MAX_AGE_MINUTES', 15),

];
