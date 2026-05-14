<?php

namespace App\Services\Courier;

use App\Infrastructure\Geo\Contracts\CourierGeoLocatorInterface;
use App\Jobs\UpdateCourierGeoRedisJob;

class CourierLocationService
{
    public function __construct(private readonly CourierGeoLocatorInterface $locator) {}

    public function updateLocation(int $firmId, int $courierId, float $lat, float $lng): void
    {
        if (! config('courier.geo_redis_enabled', false)) {
            return;
        }

        if (config('courier.geo_redis_async', false)) {
            UpdateCourierGeoRedisJob::dispatch($firmId, $courierId, $lat, $lng);

            return;
        }

        $this->locator->add($firmId, $courierId, $lat, $lng);
    }
}
