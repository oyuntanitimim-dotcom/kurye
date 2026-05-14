<?php

/**
 * Test / demo giriş bilgileri — sadece giriş ekranında gösterilir.
 * Burayı güncelleyerek e-postaları değiştirebilirsiniz (seed ile aynı olmalı).
 *
 * Şifreler bozulduysa: php artisan kurye:repair-demo-logins
 * Demo veriyi sıfırdan yüklemek için: php artisan db:seed
 */
return [
    'show_test_credentials' => env('SHOW_DEV_LOGIN_HINT', true) && env('APP_ENV') !== 'production',

    /** Tüm demo hesaplar için ortak şifre (DatabaseSeeder ile uyumlu) */
    'password_hint' => env('DEV_TEST_PASSWORD', 'password'),

    /**
     * @var list<array{label: string, email: string}>
     */
    'accounts' => [
        ['label' => 'Süper yönetici', 'email' => 'admin@kurye.local'],
        ['label' => 'Kurye şirketi yöneticisi', 'email' => 'firma-a@demo.local'],
        ['label' => 'Firma', 'email' => 'restoran@demo.local'],
        ['label' => 'Kurye', 'email' => 'kurye@demo.local'],
        ['label' => 'Müşteri', 'email' => 'musteri@demo.local'],
    ],
];
