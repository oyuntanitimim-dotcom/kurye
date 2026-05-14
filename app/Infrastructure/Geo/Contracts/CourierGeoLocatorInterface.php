<?php

declare(strict_types=1);

namespace App\Infrastructure\Geo\Contracts;

interface CourierGeoLocatorInterface
{
    /**
     * Son bilinen kurye konumunu (ör. Redis GEO) indeksler. İmplementasyon hata verirse
     * çağıran akışın (HTTP 200) bozulmaması için genelde try/catch ile yutulur.
     *
     * Anahtar firma bazlıdır: `{prefix}:{firmId}` (çok kiracı sızıntısını önlemek için).
     */
    public function add(int $firmId, int $courierId, float $latitude, float $longitude): void;

    /**
     * Teslimat noktasına belirli yarıçap içindeki kurye id'leri (mesafeye göre artan).
     *
     * @return list<int>
     */
    public function courierIdsNear(int $firmId, float $latitude, float $longitude, float $radiusKm, int $limit = 10): array;
}
