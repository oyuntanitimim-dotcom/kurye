<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Varsayılan satış kategorileri (firma türüne göre)
    |--------------------------------------------------------------------------
    |
    | Yeni firma oluşturulurken bu isimlerle boş kategoriler eklenir.
    | Anahtarlar App\Enums\RestaurantBusinessType değerleri ile eşleşmelidir.
    |
    */
    'types' => [
        'restaurant' => [
            'Çorbalar & başlangıçlar',
            'Soğuk & sıcak mezeler',
            'Ana yemekler',
            'Tatlılar',
            'İçecekler',
        ],
        'fast_food' => [
            'Tost & sandviç',
            'Burger & wrap',
            'Patates & yan lezzetler',
            'Menüler',
            'İçecekler',
        ],
        'kebab' => [
            'Döner & dürüm',
            'Kebap & ızgara',
            'Pideler',
            'Salata & garnitür',
            'İçecekler & ayran',
        ],
        'cafe' => [
            'Espresso & filtre kahve',
            'Soğuk kahve & frappe',
            'Çay & bitki çayları',
            'Pastane & tatlı',
            'Atıştırmalık',
        ],
        'bakery' => [
            'Ekmek & simit',
            'Poğaça & börek',
            'Pasta & kek',
            'Kurabiye & tatlı',
            'Kahvaltılık',
        ],
        'market' => [
            'Meyve & sebze',
            'Süt & kahvaltılık',
            'Et & tavuk & balık',
            'Temel gıda & konserve',
            'Temizlik & kişisel bakım',
            'İçecek & atıştırmalık',
        ],
        'grocery' => [
            'Şarküteri & peynir',
            'Temel gıda',
            'Atıştırmalık & bisküvi',
            'İçecek',
            'Temizlik & deterjan',
            'Kağıt & hijyen',
        ],
        'greengrocer' => [
            'Meyve',
            'Sebze',
            'Yeşillik & otlar',
            'Organik seçkiler',
            'Salata hazırlık',
        ],
        'pharmacy' => [
            'Reçetesiz ilaç & vitamin',
            'Bebek & anne bakımı',
            'Cilt bakımı',
            'Medikal sarf',
            'Sağlıklı atıştırmalık',
        ],
        'florist' => [
            'Buket & aranjman',
            'Saksı çiçekleri',
            'Kuru çiçek & dekor',
            'Özel günler',
            'Yan ürünler (çikolata vb.)',
        ],
        'pet' => [
            'Kedi maması & ödül',
            'Köpek maması & ödül',
            'Kuş & kemirgen',
            'Oyuncak & aksesuar',
            'Bakım & hijyen',
        ],
        'nuts_confectionery' => [
            'Kuruyemiş',
            'Çikolata & bar',
            'Lokum & helva',
            'Şekerleme',
            'Hediye paketleri',
        ],
        'gift' => [
            'Hediye kutuları',
            'Kupa & obje',
            'Kozmetik hediye',
            'Çikolata & kutlama',
            'Kart & ambalaj',
        ],
        'bookstore_stationery' => [
            'Kitap',
            'Defter & ajanda',
            'Kalem & yazı gereci',
            'Ofis malzemesi',
            'Hobi & sanat',
        ],
        'water_beverage_dispatch' => [
            'Damacana su',
            'Pet şişe içecek',
            'Maden suyu & soda',
            'Çay & kahve paket',
            'Galoş & depozito ürünleri',
        ],
    ],
];
