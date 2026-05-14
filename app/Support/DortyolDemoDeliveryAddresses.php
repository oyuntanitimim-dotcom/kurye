<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Demo / onarım için Dörtyol çevresinde birbirinden ayrı teslimat satırları (metin + lat/lng).
 */
final class DortyolDemoDeliveryAddresses
{
    /**
     * @return list<array{address: string, latitude: float, longitude: float}>
     */
    public static function slots(float $baseLat, float $baseLng): array
    {
        $lines = [
            [-0.014, 0.011, 'Özerli Mah. Yavuz Selim Cad. No:42, Dörtyol / Hatay'],
            [0.010, -0.008, 'Sanayi Mah. Okul Sk. No:7, Dörtyol / Hatay'],
            [-0.006, -0.012, 'Payas Yolu Mah. Cumhuriyet Cad. No:99, Dörtyol / Hatay'],
            [0.016, 0.014, 'Çaylı Mah. Atatürk Cad. No:28, Dörtyol / Hatay'],
            [-0.018, -0.005, 'Kuzuculu Yolu üzeri Hükümet Cad. No:12, Dörtyol / Hatay'],
            [0.008, 0.018, 'Numune Evler Mah. İnönü Cad. No:55, Dörtyol / Hatay'],
            [-0.009, 0.016, 'Esentepe Mah. Bahçe Sk. No:3, Dörtyol / Hatay'],
            [0.012, -0.014, 'Yeni Mah. İstiklal Cad. No:140, Dörtyol / Hatay'],
            [-0.011, -0.015, 'Organize Sanayi 2. Cad. No:9, Dörtyol / Hatay'],
            [0.019, 0.006, 'Merkez Mah. Cumhuriyet Meydanı No:2, Dörtyol / Hatay'],
            [-0.005, 0.020, 'Sahil Yolu Cad. Marina Sitesi Blok A, Dörtyol / Hatay'],
            [0.014, 0.012, 'Bahçelievler Mah. Lise Cad. No:66, Dörtyol / Hatay'],
            [-0.007, 0.009, 'Kışlaönü Mah. Ziraat Sok. No:14, Dörtyol / Hatay'],
            [0.021, -0.011, 'Yılantaş Mah. Fuar Cad. No:8, Dörtyol / Hatay'],
        ];
        $out = [];
        foreach ($lines as [$dlat, $dlng, $addr]) {
            $out[] = [
                'address' => $addr,
                'latitude' => $baseLat + $dlat,
                'longitude' => $baseLng + $dlng,
            ];
        }

        return $out;
    }
}
