<?php

declare(strict_types=1);

namespace App\Infrastructure\Geo;

use App\Infrastructure\Geo\Contracts\CourierGeoLocatorInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class RedisGeoCourierLocator implements CourierGeoLocatorInterface
{
    public function __construct(
        private readonly string $connection,
        /** @var string Kurye şirketi anahtarı öneki; gerçek anahtar: `{prefix}:{firmId}` */
        private readonly string $keyPrefix,
    ) {}

    public function add(int $firmId, int $courierId, float $latitude, float $longitude): void
    {
        try {
            $conn = Redis::connection($this->connection);
            $key = $this->keyForFirm($firmId);

            $conn->command('GEOADD', [
                $key,
                (string) $longitude,
                (string) $latitude,
                (string) $courierId,
            ]);

            $ttl = (int) config('courier.geo_redis_key_ttl_seconds', 0);
            if ($ttl > 0) {
                $conn->command('EXPIRE', [$key, (string) $ttl]);
            }
        } catch (Throwable $e) {
            Log::warning('courier_geo_redis_geoadd_failed', [
                'firm_id' => $firmId,
                'courier_id' => $courierId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function courierIdsNear(int $firmId, float $latitude, float $longitude, float $radiusKm, int $limit = 10): array
    {
        try {
            $raw = Redis::connection($this->connection)->command('GEORADIUS', [
                $this->keyForFirm($firmId),
                (string) $longitude,
                (string) $latitude,
                (string) $radiusKm,
                'km',
                'WITHDIST',
                'ASC',
                'COUNT',
                (string) $limit,
            ]);
        } catch (Throwable $e) {
            Log::warning('courier_geo_redis_georadius_failed', [
                'firm_id' => $firmId,
                'message' => $e->getMessage(),
            ]);

            return [];
        }

        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $row) {
            if (is_array($row) && isset($row[0])) {
                $ids[] = (int) $row[0];
            } elseif (is_string($row)) {
                $ids[] = (int) $row;
            }
        }

        return $ids;
    }

    private function keyForFirm(int $firmId): string
    {
        return $this->keyPrefix.':'.$firmId;
    }
}
