<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class OsrmClient
{
    public function route(float $fromLat, float $fromLng, float $toLat, float $toLng): ?array
    {
        $baseUrl = (string) config('routing.osrm.base_url', '');
        if ($baseUrl === '') {
            return null;
        }

        $profile = (string) config('routing.osrm.profile', 'driving');
        $timeout = (int) config('routing.osrm.timeout_seconds', 6);

        $coordString = $fromLng.','.$fromLat.';'.$toLng.','.$toLat;
        $url = $baseUrl.'/route/v1/'.$profile.'/'.$coordString;

        try {
            $resp = Http::timeout($timeout)
                ->acceptJson()
                ->get($url, [
                    'overview' => 'full',
                    'geometries' => 'geojson',
                    'steps' => 'false',
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $resp->ok()) {
            return null;
        }

        $json = $resp->json();
        if (! is_array($json)) {
            return null;
        }

        return $json;
    }

    public function table(array $coordinates): ?array
    {
        $baseUrl = (string) config('routing.osrm.base_url', '');
        if ($baseUrl === '') {
            return null;
        }

        $profile = (string) config('routing.osrm.profile', 'driving');
        $timeout = (int) config('routing.osrm.timeout_seconds', 6);

        $coordString = $this->coordinatesToString($coordinates);
        if ($coordString === '') {
            return null;
        }

        $url = $baseUrl.'/table/v1/'.$profile.'/'.$coordString;

        try {
            $resp = Http::timeout($timeout)
                ->acceptJson()
                ->get($url, [
                    'annotations' => 'duration,distance',
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $resp->ok()) {
            return null;
        }

        $json = $resp->json();
        if (! is_array($json)) {
            return null;
        }

        return $json;
    }

    /**
     * @param array<int, array{lat: float|int, lng: float|int}> $coordinates
     */
    private function coordinatesToString(array $coordinates): string
    {
        $parts = [];
        foreach ($coordinates as $pt) {
            if (! isset($pt['lat'], $pt['lng'])) {
                continue;
            }
            $lat = (float) $pt['lat'];
            $lng = (float) $pt['lng'];
            $parts[] = $lng.','.$lat;
        }

        return implode(';', $parts);
    }
}

