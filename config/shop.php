<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Mağaza — varsayılan teslimat ücreti (₺)
    |--------------------------------------------------------------------------
    |
    | Firma ayarında (settings.default_delivery_fee) yoksa bu değer kullanılır.
    |
    */
    'default_delivery_fee' => (float) env('SHOP_DEFAULT_DELIVERY_FEE', 15),

    /*
    | Mesafe bazlı ücret (restoran + adres enlem/boylam varsa).
    | Ücret = max(min, min(max, base + km * per_km))
    */
    'delivery_distance_base_fee' => (float) env('SHOP_DELIVERY_DISTANCE_BASE_FEE', 15),

    'delivery_distance_per_km' => (float) env('SHOP_DELIVERY_DISTANCE_PER_KM', 4),

    'delivery_distance_min_fee' => (float) env('SHOP_DELIVERY_DISTANCE_MIN_FEE', 10),

    'delivery_distance_max_fee' => (float) env('SHOP_DELIVERY_DISTANCE_MAX_FEE', 120),

];
