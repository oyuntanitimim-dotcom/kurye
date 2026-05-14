<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Uygulama içi bildirim job kuyruğu
    |--------------------------------------------------------------------------
    |
    | CreateInAppNotificationJob bu kuyruğa gider; Horizon’da hızlı işlerden ayrılır.
    |
    */
    'in_app_queue' => env('QUEUE_NOTIFICATIONS', 'notifications'),

];
