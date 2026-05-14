<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RestaurantBusinessType;
use App\Modules\Integrations\Services\MarketplaceIngestService;
use App\Modules\Firms\Models\Firm;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Restaurants\Models\RestaurantCategory;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketplaceIngestAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_ingest_creates_delivery_address_with_geocoded_coords(): void
    {
        foreach ([Role::SUPER_ADMIN, Role::FIRM_ADMIN, Role::RESTAURANT, Role::COURIER, Role::CUSTOMER] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }

        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                ['lat' => '38.4200000', 'lon' => '27.1300000'],
            ], 200),
        ]);

        $firm = Firm::query()->create([
            'name' => 'Test',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'firm-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);

        $restaurant = Restaurant::query()->create([
            'firm_id' => $firm->id,
            'name' => 'Restoran',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 38.42,
            'longitude' => 27.13,
        ]);

        $cat = RestaurantCategory::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Genel',
            'sort_order' => 0,
        ]);

        $product = Product::query()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $cat->id,
            'name' => 'Ürün A',
            'price' => 50.00,
            'status' => 'active',
            'stock' => 99,
        ]);

        $ingest = app(MarketplaceIngestService::class);
        $order = $ingest->ingestFromPayload($firm->id, 'pilot', [
            'external_order_id' => 'EXT-ADDR-1',
            'restaurant_id' => $restaurant->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'customer_name' => 'M',
            'customer_phone' => '05550001122',
            'delivery_address' => 'Konak, İzmir',
            'payment_method' => 'cash_on_delivery',
            'delivery_fee' => 0,
        ]);

        $order->refresh();
        $this->assertNotNull($order->user_id);
        $this->assertNotNull($order->delivery_address_id);
        $this->assertEqualsWithDelta(38.42, (float) $order->deliveryAddress?->latitude, 0.0001);
        $this->assertEqualsWithDelta(27.13, (float) $order->deliveryAddress?->longitude, 0.0001);
    }

    public function test_marketplace_ingest_uses_payload_coordinates_without_geocoding(): void
    {
        foreach ([Role::SUPER_ADMIN, Role::FIRM_ADMIN, Role::RESTAURANT, Role::COURIER, Role::CUSTOMER] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }

        Http::fake(); // geocode çağrısı olmamalı

        $firm = Firm::query()->create([
            'name' => 'Test',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'firm-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);

        $restaurant = Restaurant::query()->create([
            'firm_id' => $firm->id,
            'name' => 'Restoran',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 38.42,
            'longitude' => 27.13,
        ]);

        $cat = RestaurantCategory::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Genel',
            'sort_order' => 0,
        ]);

        $product = Product::query()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $cat->id,
            'name' => 'Ürün A',
            'price' => 50.00,
            'status' => 'active',
            'stock' => 99,
        ]);

        $ingest = app(MarketplaceIngestService::class);
        $order = $ingest->ingestFromPayload($firm->id, 'pilot', [
            'external_order_id' => 'EXT-ADDR-2',
            'restaurant_id' => $restaurant->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'customer_name' => 'M',
            'customer_phone' => '05550001122',
            'deliveryAddress' => [
                'address' => 'Konak, İzmir',
                'lat' => 38.43001,
                'lng' => 27.14002,
            ],
            'payment_method' => 'cash_on_delivery',
            'delivery_fee' => 0,
        ]);

        $order->refresh();
        $this->assertNotNull($order->delivery_address_id);
        $this->assertEqualsWithDelta(38.43001, (float) $order->deliveryAddress?->latitude, 0.000001);
        $this->assertEqualsWithDelta(27.14002, (float) $order->deliveryAddress?->longitude, 0.000001);

        Http::assertNothingSent();
    }
}

