<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\RestaurantBusinessType;
use App\Models\User;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderStateService;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderSettlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivered_order_snapshots_platform_fee_and_restaurant_per_delivery_fee(): void
    {
        foreach ([
            Role::SUPER_ADMIN,
            Role::FIRM_ADMIN,
            Role::RESTAURANT,
            Role::COURIER,
            Role::CUSTOMER,
        ] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }

        $firm = Firm::query()->create([
            'name' => 'Settle Co',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'settle-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 2.5,
            'default_restaurant_fee_per_delivery' => 8.5,
            'status' => 'active',
        ]);

        $restaurant = Restaurant::query()->create([
            'firm_id' => $firm->id,
            'name' => 'Rest',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 38.42,
            'longitude' => 27.13,
            'fee_per_delivery' => null,
        ]);

        $customer = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::CUSTOMER)->value('id'),
        ]);

        $order = Order::query()->create([
            'firm_id' => $firm->id,
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'courier_id' => null,
            'delivery_address_id' => null,
            'status' => OrderStatus::OnTheWay->value,
            'total_price' => 110.00,
            'delivery_fee' => 10.00,
            'discount_amount' => 5.00,
            'payment_method' => 'cash_on_delivery',
        ]);

        app(OrderStateService::class)->transition($order->fresh(), OrderStatus::Delivered);

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered->value, $order->status);
        $this->assertEquals('2.50', (string) $order->platform_fee_amount);
        $this->assertEquals('8.50', (string) $order->restaurant_commission_amount);
        $this->assertNull($order->courier_payout_amount);
    }

    public function test_delivered_order_snapshots_courier_per_delivery_payout(): void
    {
        foreach ([
            Role::SUPER_ADMIN,
            Role::FIRM_ADMIN,
            Role::RESTAURANT,
            Role::COURIER,
            Role::CUSTOMER,
        ] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }

        $firm = Firm::query()->create([
            'name' => 'Settle C',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'settlec-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);

        $restaurant = Restaurant::query()->create([
            'firm_id' => $firm->id,
            'name' => 'Rest',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 38.42,
            'longitude' => 27.13,
        ]);

        $customer = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::CUSTOMER)->value('id'),
        ]);

        $courierUser = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::COURIER)->value('id'),
        ]);

        $courier = Courier::query()->create([
            'firm_id' => $firm->id,
            'user_id' => $courierUser->id,
            'name' => 'Kurye',
            'phone' => '05551112233',
            'vehicle_type' => 'motosiklet',
            'status' => 'active',
            'compensation_type' => 'per_delivery',
            'compensation_per_delivery' => 42.5,
        ]);

        $order = Order::query()->create([
            'firm_id' => $firm->id,
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'courier_id' => $courier->id,
            'delivery_address_id' => null,
            'status' => OrderStatus::OnTheWay->value,
            'total_price' => 50.00,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
        ]);

        app(OrderStateService::class)->transition($order->fresh(), OrderStatus::Delivered);

        $order->refresh();
        $this->assertEquals('42.50', (string) $order->courier_payout_amount);
        $this->assertEquals('0.00', (string) $order->restaurant_commission_amount);
    }

    public function test_restaurant_specific_fee_overrides_firm_default(): void
    {
        foreach ([
            Role::SUPER_ADMIN,
            Role::FIRM_ADMIN,
            Role::RESTAURANT,
            Role::COURIER,
            Role::CUSTOMER,
        ] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }

        $firm = Firm::query()->create([
            'name' => 'Settle B',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'settleb-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 1,
            'default_restaurant_fee_per_delivery' => 10,
            'status' => 'active',
        ]);

        $restaurant = Restaurant::query()->create([
            'firm_id' => $firm->id,
            'name' => 'Rest',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 38.42,
            'longitude' => 27.13,
            'fee_per_delivery' => 25,
        ]);

        $customer = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::CUSTOMER)->value('id'),
        ]);

        $order = Order::query()->create([
            'firm_id' => $firm->id,
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'courier_id' => null,
            'delivery_address_id' => null,
            'status' => OrderStatus::PickedUp->value,
            'total_price' => 100.00,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
        ]);

        app(OrderStateService::class)->transition($order->fresh(), OrderStatus::Delivered);

        $order->refresh();
        $this->assertEquals('1.00', (string) $order->platform_fee_amount);
        $this->assertEquals('25.00', (string) $order->restaurant_commission_amount);
        $this->assertNull($order->courier_payout_amount);
    }
}
