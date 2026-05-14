<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Infrastructure\Geo\Contracts\CourierGeoLocatorInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Yüksek trafikte POST /courier/location cevabını kısaltmak için Redis GEOADD kuyruğa alınabilir.
 */
class UpdateCourierGeoRedisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $firmId,
        public int $courierId,
        public float $latitude,
        public float $longitude,
    ) {
        $this->onQueue((string) config('courier.geo_redis_queue', 'default'));
    }

    public function handle(CourierGeoLocatorInterface $locator): void
    {
        if (! config('courier.geo_redis_enabled', false)) {
            return;
        }

        $locator->add($this->firmId, $this->courierId, $this->latitude, $this->longitude);
    }
}
