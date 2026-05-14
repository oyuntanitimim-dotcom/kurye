<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\RestaurantBusinessType;
use App\Models\User;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Restaurants\Models\RestaurantCategory;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOrdersFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_filter_excludes_terminal_orders(): void
    {
        Role::query()->firstOrCreate(['name' => Role::SUPER_ADMIN]);

        $superAdmin = User::factory()->create([
            'firm_id' => null,
            'role_id' => Role::query()->where('name', Role::SUPER_ADMIN)->value('id'),
        ]);

        $ctx = $this->minimalFirmRestaurant();

        Order::query()->create([
            'firm_id' => $ctx['firm']->id,
            'source' => OrderSource::Phone->value,
            'user_id' => null,
            'restaurant_id' => $ctx['restaurant']->id,
            'courier_id' => null,
            'delivery_address_id' => null,
            'status' => OrderStatus::Delivered->value,
            'total_price' => 50,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'customer_name' => 'ADMIN_FILTER_DONE_UNIQUE',
        ]);

        Order::query()->create([
            'firm_id' => $ctx['firm']->id,
            'source' => OrderSource::Phone->value,
            'user_id' => null,
            'restaurant_id' => $ctx['restaurant']->id,
            'courier_id' => null,
            'delivery_address_id' => null,
            'status' => OrderStatus::Accepted->value,
            'total_price' => 40,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'customer_name' => 'ADMIN_FILTER_OPEN_UNIQUE',
        ]);

        $html = $this->actingAs($superAdmin)->get('/admin/siparisler?active=1')->assertOk()->getContent();
        $this->assertStringContainsString('ADMIN_FILTER_OPEN_UNIQUE', $html);
        $this->assertStringNotContainsString('ADMIN_FILTER_DONE_UNIQUE', $html);
    }

    public function test_awaiting_courier_filter_shows_only_ready_without_courier(): void
    {
        Role::query()->firstOrCreate(['name' => Role::SUPER_ADMIN]);
        foreach ([Role::COURIER] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }

        $superAdmin = User::factory()->create([
            'firm_id' => null,
            'role_id' => Role::query()->where('name', Role::SUPER_ADMIN)->value('id'),
        ]);

        $ctx = $this->minimalFirmRestaurant();

        $courierUser = User::factory()->create([
            'firm_id' => $ctx['firm']->id,
            'role_id' => Role::query()->where('name', Role::COURIER)->value('id'),
        ]);

        $courier = Courier::query()->create([
            'firm_id' => $ctx['firm']->id,
            'user_id' => $courierUser->id,
            'name' => 'Kurye',
            'phone' => '05550001122',
            'status' => 'active',
        ]);

        Order::query()->create([
            'firm_id' => $ctx['firm']->id,
            'source' => OrderSource::Phone->value,
            'user_id' => null,
            'restaurant_id' => $ctx['restaurant']->id,
            'courier_id' => $courier->id,
            'delivery_address_id' => null,
            'status' => OrderStatus::Ready->value,
            'total_price' => 30,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'customer_name' => 'ADMIN_AWAIT_HAS_COURIER',
        ]);

        Order::query()->create([
            'firm_id' => $ctx['firm']->id,
            'source' => OrderSource::Phone->value,
            'user_id' => null,
            'restaurant_id' => $ctx['restaurant']->id,
            'courier_id' => null,
            'delivery_address_id' => null,
            'status' => OrderStatus::Ready->value,
            'total_price' => 20,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'customer_name' => 'ADMIN_AWAIT_NO_COURIER',
        ]);

        Order::query()->create([
            'firm_id' => $ctx['firm']->id,
            'source' => OrderSource::Phone->value,
            'user_id' => null,
            'restaurant_id' => $ctx['restaurant']->id,
            'courier_id' => null,
            'delivery_address_id' => null,
            'status' => OrderStatus::Accepted->value,
            'total_price' => 10,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'customer_name' => 'ADMIN_AWAIT_ACCEPTED',
        ]);

        $html = $this->actingAs($superAdmin)->get('/admin/siparisler?awaiting_courier=1')->assertOk()->getContent();
        $this->assertStringContainsString('ADMIN_AWAIT_NO_COURIER', $html);
        $this->assertStringNotContainsString('ADMIN_AWAIT_HAS_COURIER', $html);
        $this->assertStringNotContainsString('ADMIN_AWAIT_ACCEPTED', $html);
    }

    /**
     * @return array{firm: Firm, restaurant: Restaurant, product: Product}
     */
    private function minimalFirmRestaurant(): array
    {
        $firm = Firm::query()->create([
            'name' => 'F',
            'city' => 'c',
            'district' => 'd',
            'domain' => 'd-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);

        $restaurant = Restaurant::query()->create([
            'firm_id' => $firm->id,
            'name' => 'R',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 1,
            'longitude' => 1,
        ]);

        $cat = RestaurantCategory::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'C',
            'sort_order' => 0,
        ]);

        $product = Product::query()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $cat->id,
            'name' => 'P',
            'price' => 10,
            'status' => 'active',
            'stock' => 9,
        ]);

        return ['firm' => $firm, 'restaurant' => $restaurant, 'product' => $product];
    }
}
