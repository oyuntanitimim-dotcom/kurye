<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Ödeme sürücüsü
    |--------------------------------------------------------------------------
    |
    | local — anında onaylı demo (yerel geliştirme)
    | null  — eski NullPaymentGateway (provider kaydı "null")
    |
    */
    'driver' => env('PAYMENT_DRIVER', 'local'),

];
