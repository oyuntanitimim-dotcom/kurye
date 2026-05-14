<?php

declare(strict_types=1);

namespace App\Infrastructure\Geo;

use App\Infrastructure\Geo\Contracts\CourierGeoLocatorInterface;

final class NullCourierGeoLocator implements CourierGeoLocatorInterface
{
    public function add(int $firmId, int $courierId, float $latitude, float $longitude): void
    {
        // Redis GEO kapalı veya kullanılmıyor.
    }

    public function courierIdsNear(int $firmId, float $latitude, float $longitude, float $radiusKm, int $limit = 10): array
    {
        return [];
    }
}
