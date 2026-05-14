<?php

declare(strict_types=1);

namespace App\Services\Geocoding;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenStreetMap Nominatim (metin → koordinat).
 * Kullanım politikası: anlamlı User-Agent ve makul istek oranı gerekir.
 *
 * @return array{latitude: float, longitude: float}|null
 */
final class NominatimGeocoder
{
    public function geocodeFreeText(string $address): ?array
    {
        if (! config('services.nominatim.enabled', true)) {
            return null;
        }

        $address = trim($address);
        if ($address === '') {
            return null;
        }

        $userAgent = (string) config('services.nominatim.user_agent', '');
        if ($userAgent === '') {
            Log::warning('NOMINATIM_USER_AGENT boş; geocoding atlandı.');

            return null;
        }

        $base = rtrim((string) config('services.nominatim.url', 'https://nominatim.openstreetmap.org'), '/');
        $country = (string) config('services.nominatim.country_codes', 'tr');

        try {
            $response = Http::timeout((int) config('services.nominatim.timeout_seconds', 8))
                ->connectTimeout(4)
                ->withHeaders([
                    'User-Agent' => $userAgent,
                    'Accept-Language' => 'tr',
                ])
                ->get($base.'/search', [
                    'format' => 'json',
                    'limit' => 1,
                    'q' => $address,
                    'countrycodes' => $country,
                ]);
        } catch (\Throwable $e) {
            Log::debug('Nominatim isteği başarısız: '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();
        if (! is_array($json) || $json === []) {
            return null;
        }

        $first = $json[0] ?? null;
        if (! is_array($first)) {
            return null;
        }

        if (! isset($first['lat'], $first['lon'])) {
            return null;
        }

        $lat = (float) $first['lat'];
        $lon = (float) $first['lon'];

        return ['latitude' => $lat, 'longitude' => $lon];
    }
}
