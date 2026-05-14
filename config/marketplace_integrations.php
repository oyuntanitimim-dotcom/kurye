<?php

/**
 * Online satış / pazar yeri entegrasyonları: resmi dokümantasyon ve kısa teknik notlar.
 * Sipariş alımı bu projede normalize edilmiş webhook gövdesi ile yapılır; platformların
 * kendi şemalarına dönüştürmek için ara katman (middleware worker veya platform SDK) gerekir.
 *
 * @see https://developer.yemeksepeti.com/
 * @see https://developers.getir.com/food/api-documentation
 * @see https://developers.tgoapps.com/
 */
return [
    /** Gelen webhook: IP başına dakikada en fazla istek (RateLimiter: integration-webhook). */
    'webhook_per_minute' => max(10, (int) env('INTEGRATION_WEBHOOK_PER_MINUTE', 120)),

    /** Gelen webhook ham JSON gövdesi üst sınırı (bayt). */
    'webhook_max_body_bytes' => max(65536, (int) env('INTEGRATION_WEBHOOK_MAX_BODY_BYTES', 524288)),

    /** Giden sipariş durumu bildirimi (order_status_webhook_url). */
    'status_push' => [
        'timeout_seconds' => max(3, (int) env('MARKETPLACE_STATUS_PUSH_TIMEOUT', 12)),
        'http_retries' => max(1, (int) env('MARKETPLACE_STATUS_PUSH_HTTP_RETRIES', 3)),
        'http_retry_sleep_ms' => max(0, (int) env('MARKETPLACE_STATUS_PUSH_HTTP_RETRY_MS', 400)),
        /** database/redis kuyrukta: HTTP hâlâ başarısızsa job ertelenir (sync/null’da kapalı). */
        'queue_retry_enabled' => filter_var(
            env('MARKETPLACE_STATUS_QUEUE_RETRY', true),
            FILTER_VALIDATE_BOOL
        ),
        'queue_max_attempts' => max(1, (int) env('MARKETPLACE_STATUS_QUEUE_TRIES', 4)),
        /** Her başarısız denemeden sonra bekleme (saniye); uzunluk queue_max_attempts - 1 olmalı. */
        'queue_release_seconds' => [90, 300, 600],
        /** ShouldBeUnique: aynı (sipariş, durum) için ikinci dispatch süresince cache kilidi (sn). */
        'unique_lock_seconds' => max(60, (int) env('MARKETPLACE_STATUS_UNIQUE_LOCK', 900)),
        /** Horizon / queue:work ile dinlenecek kuyruk (dış HTTP, yavaş olabilir). */
        'queue' => env('QUEUE_INTEGRATIONS', 'integrations'),
    ],

    'providers' => [
        'yemeksepeti' => [
            'label' => 'Yemeksepeti',
            'description' => 'Partner API: OAuth 2.0 (client credentials), katalog, sipariş ve kampanya uçları. Kimlik bilgileri hesap yöneticisi / Partner Portal üzerinden.',
            'documentation_url' => 'https://developer.yemeksepeti.com/',
            'documentation_url_alt' => 'https://integration.yemeksepeti.com/en/',
        ],
        'getir_yemek' => [
            'label' => 'Getir Yemek',
            'description' => 'GetirFood API: geliştirici portalından başvuru; oturum anahtarı ve restoran gizli anahtarı ile uç noktalara erişim.',
            'documentation_url' => 'https://developers.getir.com/food/api-documentation',
            'documentation_url_alt' => 'https://developers.getir.com/',
        ],
        'trendyol_yemek' => [
            'label' => 'Trendyol Yemek (Trendyol Go)',
            'description' => 'Yemek tarafı için Trendyol Go / TGO geliştirici portalı (menü ve sipariş akışları). Genel Trendyol Marketplace API’sinden ayrıdır.',
            'documentation_url' => 'https://developers.tgoapps.com/',
            'documentation_url_alt' => 'https://developers.trendyol.com/',
        ],
        'migros_yemek' => [
            'label' => 'Migros Yemek',
            'description' => 'Genelde POS / entegrasyon ortağı veya Migros Yemek panelinden verilen API anahtarı ile çalışılır; herkese açık tek bir geliştirici portalı olmayabilir.',
            'documentation_url' => 'https://bilgibankasi.akinsoft.net/tr/home/makale/3691-migros-yemek-entegrasyonu-yardim-dokumani',
            'documentation_url_alt' => null,
        ],
        'pilot' => [
            'label' => 'Pilot / test',
            'description' => 'Geliştirme ve test için dahili sağlayıcı kodu; üretimde kullanmayın.',
            'documentation_url' => null,
            'documentation_url_alt' => null,
        ],
    ],
];
