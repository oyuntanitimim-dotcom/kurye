<?php

declare(strict_types=1);

return [
    /*
    | Pazarlama üst başlığında gösterilen marka (MARKETING_BRAND_NAME boşsa APP_NAME, o da Laravel ise Ab Kurye).
    */
    'brand_name' => (static function (): string {
        $m = env('MARKETING_BRAND_NAME');
        if (is_string($m) && trim($m) !== '') {
            return trim($m);
        }
        $app = trim((string) env('APP_NAME', ''));
        if ($app === '' || strcasecmp($app, 'Laravel') === 0) {
            return 'Ab Kurye';
        }

        return $app;
    })(),

    /*
    | Üst menü logosundaki kısa işaret (ör. Ab Kurye → Ab).
    */
    'logo_mark' => env('MARKETING_LOGO_MARK', 'Ab'),

    /*
    | İletişim formu gönderildiğinde bilgilendirme e-postası (boşsa sadece veritabanına yazılır).
    */
    'lead_notify_email' => env('MARKETING_LEAD_NOTIFY_EMAIL'),

    /*
    | Opsiyonel: harici bir iletişim e-postası (mailto veya form metninde kullanılabilir).
    */
    'public_contact_email' => env('MARKETING_PUBLIC_CONTACT_EMAIL'),
];
