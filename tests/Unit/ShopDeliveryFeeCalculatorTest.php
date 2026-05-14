<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\RestaurantBusinessType;
use App\Modules\Firms\Models\Firm;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Address;
use App\Services\Shop\DeliveryFeeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShopDeliveryFeeCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_flat_fee_when_distance_disabled(): void
    {
        $firm = $this->makeFirm(['default_delivery_fee' => 33, 'delivery_use_distance' => false]);
        $restaurant = $this->restaurant($firm, 40.0, 29.0);
        $calc = new DeliveryFeeCalculator;

        $fee = $calc->compute($firm, $restaurant, $this->address(40.1, 29.1));

        $this->assertSame(33.0, $fee);
    }

    public function test_falls_back_to_flat_when_coordinates_missing(): void
    {
        $firm = $this->makeFirm([
            'default_delivery_fee' => 12,
            'delivery_use_distance' => true,
            'delivery_distance_base_fee' => 99,
            'delivery_distance_per_km' => 50,
            'delivery_distance_min_fee' => 1,
            'delivery_distance_max_fee' => 200,
        ]);
        $restaurant = $this->restaurant($firm, null, null);
        $calc = new DeliveryFeeCalculator;

        $fee = $calc->compute($firm, $restaurant, $this->address(38.0, 27.0));

        $this->assertSame(12.0, $fee);
    }

    public function test_restaurant_shop_delivery_fee_overrides_firm_and_distance(): void
    {
        $firm = $this->makeFirm([
            'default_delivery_fee' => 40,
            'delivery_use_distance' => true,
            'delivery_distance_base_fee' => 10,
            'delivery_distance_per_km' => 100,
            'delivery_distance_min_fee' => 1,
            'delivery_distance_max_fee' => 500,
        ]);
        $restaurant = $this->restaurant($firm, 38.0, 27.0);
        $restaurant->shop_delivery_fee = 6.5;
        $restaurant->save();

        $calc = new DeliveryFeeCalculator;

        $fee = $calc->compute($firm, $restaurant, $this->address(39.0, 28.0));

        $this->assertSame(6.5, $fee);
    }

    public function test_distance_formula_applies_minimum_when_raw_below_min(): void
    {
        $firm = $this->makeFirm([
            'default_delivery_fee' => 5,
            'delivery_use_distance' => true,
            'delivery_distance_base_fee' => 10,
            'delivery_distance_per_km' => 2,
            'delivery_distance_min_fee' => 15,
            'delivery_distance_max_fee' => 40,
        ]);
        $restaurant = $this->restaurant($firm, 38.0, 27.0);
        $calc = new DeliveryFeeCalculator;

        $fee = $calc->compute($firm, $restaurant, $this->address(38.0, 27.0));

        $this->assertSame(15.0, $fee);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function makeFirm(array $settings): Firm
    {
        return Firm::query()->create([
            'name' => 'T',
            'city' => 'c',
            'district' => 'd',
            'domain' => 't-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
            'settings' => $settings,
        ]);
    }

    private function restaurant(Firm $firm, ?float $lat, ?float $lng): Restaurant
    {
        return Restaurant::query()->create([
            'firm_id' => $firm->id,
            'name' => 'R',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => $lat,
            'longitude' => $lng,
        ]);
    }

    private function address(float $lat, float $lng): Address
    {
        return new Address([
            'user_id' => 1,
            'title' => 'Ev',
            'address' => 'X',
            'latitude' => $lat,
            'longitude' => $lng,
        ]);
    }
}
