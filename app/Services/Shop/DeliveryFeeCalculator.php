<?php

declare(strict_types=1);

namespace App\Services\Shop;

use App\Modules\Firms\Models\Firm;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Address;
use App\Support\Geo\Haversine;

final class DeliveryFeeCalculator
{
    /**
     * @param  Restaurant|null  $restaurant  Sepetteki restoran (mesafe modu için).
     * @param  Address|null  $address  Teslimat adresi; yoksa veya koordinat eksikse sabit ücret.
     */
    public function compute(Firm $firm, ?Restaurant $restaurant, ?Address $address): float
    {
        if ($restaurant !== null && $restaurant->shop_delivery_fee !== null) {
            return max(0.0, round((float) $restaurant->shop_delivery_fee, 2));
        }

        $op = $firm->mergedOperationSettings();

        if (! (bool) ($op['delivery_use_distance'] ?? false)) {
            return $this->flatFee($op);
        }

        if ($restaurant === null || $address === null) {
            return $this->flatFee($op);
        }

        $rlat = $restaurant->latitude;
        $rlng = $restaurant->longitude;
        $alat = $address->latitude;
        $alng = $address->longitude;

        if ($rlat === null || $rlng === null || $alat === null || $alng === null) {
            return $this->flatFee($op);
        }

        $km = Haversine::distanceKm(
            (float) $rlat,
            (float) $rlng,
            (float) $alat,
            (float) $alng,
        );

        $base = (float) ($op['delivery_distance_base_fee'] ?? config('shop.delivery_distance_base_fee', 15));
        $perKm = (float) ($op['delivery_distance_per_km'] ?? config('shop.delivery_distance_per_km', 4));
        $minF = (float) ($op['delivery_distance_min_fee'] ?? config('shop.delivery_distance_min_fee', 10));
        $maxF = (float) ($op['delivery_distance_max_fee'] ?? config('shop.delivery_distance_max_fee', 120));

        $raw = $base + $km * $perKm;
        $fee = max($minF, min($maxF, $raw));

        return max(0.0, round($fee, 2));
    }

    /** @param  array<string, mixed>  $op */
    private function flatFee(array $op): float
    {
        $fee = (float) ($op['default_delivery_fee'] ?? config('shop.default_delivery_fee', 15));

        return max(0.0, round($fee, 2));
    }
}
