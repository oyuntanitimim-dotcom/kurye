<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Finans / raporlarda yalnızca online ödemeli siparişler
    |--------------------------------------------------------------------------
    |
    | true iken teslim edilmiş siparişlerde ciro, kesinti, kurye ücreti ve
    | mutabakat toplamları yalnızca payment_method = "online" kayıtlarından
    | hesaplanır. Kapıda nakit / kapıda kart (kuryenin tahsil ettiği) tutarlar
    | bu özetlere dahil edilmez. Kapatmak için .env: KURYE_FINANCE_ONLINE_ORDERS_ONLY=false
    |
    */
    'finance_online_orders_only' => (bool) env('KURYE_FINANCE_ONLINE_ORDERS_ONLY', false),
];
