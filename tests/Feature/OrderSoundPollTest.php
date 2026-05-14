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
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Address;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderSoundPollTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_poll_bootstrap_then_new_pending(): void
    {
        $this->seedRoles();
        $ctx = $this->makeRestaurantContext();

        $this->actingAs($ctx['user'])
            ->getJson(route('restaurant.orders.poll', ['bootstrap' => '1']))
            ->assertOk()
            ->assertJson(['new_pending' => false, 'new_external_pending' => false, 'max_id' => 0, 'external_pending_count' => 0]);

        $order = Order::query()->create([
            'firm_id' => $ctx['firm']->id,
            'source' => 'phone',
            'user_id' => null,
            'restaurant_id' => $ctx['restaurant']->id,
            'courier_id' => null,
            'delivery_address_id' => null,
            'status' => OrderStatus::Pending->value,
            'total_price' => 50,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'customer_name' => 'A',
            'customer_phone' => '0555',
        ]);

        $this->actingAs($ctx['user'])
            ->getJson(route('restaurant.orders.poll', ['since_id' => 0]))
            ->assertOk()
            ->assertJson([
                'new_pending' => true,
                'new_external_pending' => false,
                'max_id' => $order->id,
                'external_pending_count' => 0,
            ]);

        $this->actingAs($ctx['user'])
            ->getJson(route('restaurant.orders.poll', ['since_id' => $order->id]))
            ->assertOk()
            ->assertJson([
                'new_pending' => false,
                'new_external_pending' => false,
                'max_id' => $order->id,
                'external_pending_count' => 0,
            ]);
    }

    public function test_restaurant_poll_external_pending_for_marketplace_order(): void
    {
        $this->seedRoles();
        $ctx = $this->makeRestaurantContext();

        $this->actingAs($ctx['user'])
            ->getJson(route('restaurant.orders.poll', ['bootstrap' => '1']))
            ->assertOk();

        $order = Order::query()->create([
            'firm_id' => $ctx['firm']->id,
            'source' => OrderSource::Marketplace->value,
            'marketplace_provider' => 'yemeksepeti',
            'user_id' => null,
            'restaurant_id' => $ctx['restaurant']->id,
            'courier_id' => null,
            'delivery_address_id' => null,
            'status' => OrderStatus::Pending->value,
            'total_price' => 50,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'customer_name' => 'Ext',
            'customer_phone' => '0555',
        ]);

        $this->actingAs($ctx['user'])
            ->getJson(route('restaurant.orders.poll', ['since_id' => 0]))
            ->assertOk()
            ->assertJson([
                'new_pending' => true,
                'new_external_pending' => true,
                'max_id' => $order->id,
                'external_pending_count' => 1,
            ]);
    }

    public function test_firm_watch_board_signature_updates_when_order_touched(): void
    {
        $this->seedRoles();
        $ctx = $this->makeRestaurantContext();

        $firmUser = User::factory()->create([
            'firm_id' => $ctx['firm']->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
            'restaurant_id' => null,
        ]);

        $order = Order::query()->create([
            'firm_id' => $ctx['firm']->id,
            'source' => 'phone',
            'user_id' => null,
            'restaurant_id' => $ctx['restaurant']->id,
            'courier_id' => null,
            'delivery_address_id' => null,
            'status' => OrderStatus::Preparing->value,
            'total_price' => 40,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'customer_name' => 'B',
            'customer_phone' => '0556',
        ]);

        $sig1 = $this->actingAs($firmUser)
            ->getJson(route('firm.orders.watch_board'))
            ->assertOk()
            ->json('signature');

        $order->update([
            'status' => OrderStatus::Ready->value,
            'restaurant_courier_requested_at' => now(),
        ]);

        $sig2 = $this->actingAs($firmUser)
            ->getJson(route('firm.orders.watch_board'))
            ->assertOk()
            ->json('signature');

        $this->assertNotSame($sig1, $sig2);
    }

    public function test_firm_poll_detects_pending_across_restaurants(): void
    {
        $this->seedRoles();
        $ctx = $this->makeRestaurantContext();

        $firmUser = User::factory()->create([
            'firm_id' => $ctx['firm']->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
            'restaurant_id' => null,
        ]);

        $this->actingAs($firmUser)
            ->getJson(route('firm.orders.poll', ['bootstrap' => '1']))
            ->assertOk()
            ->assertJson([
                'new_pending' => false,
                'max_id' => 0,
                'ready_awaiting_courier_count' => 0,
                'courier_request_max_order_id' => 0,
                'courier_request_signal_unix' => 0,
            ]);

        $order = Order::query()->create([
            'firm_id' => $ctx['firm']->id,
            'source' => 'phone',
            'user_id' => null,
            'restaurant_id' => $ctx['restaurant']->id,
            'courier_id' => null,
            'delivery_address_id' => null,
            'status' => OrderStatus::Pending->value,
            'total_price' => 40,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'customer_name' => 'B',
            'customer_phone' => '0556',
        ]);

        $this->actingAs($firmUser)
            ->getJson(route('firm.orders.poll', ['since_id' => 0]))
            ->assertOk()
            ->assertJson([
                'new_pending' => true,
                'max_id' => $order->id,
                'ready_awaiting_courier_count' => 0,
                'courier_request_max_order_id' => 0,
                'courier_request_signal_unix' => 0,
            ]);
    }

    public function test_firm_poll_ready_awaiting_courier_stats(): void
    {
        $this->seedRoles();
        $ctx = $this->makeRestaurantContext();

        $firmUser = User::factory()->create([
            'firm_id' => $ctx['firm']->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
            'restaurant_id' => null,
        ]);

        $ready = Order::query()->create([
            'firm_id' => $ctx['firm']->id,
            'source' => 'phone',
            'user_id' => null,
            'restaurant_id' => $ctx['restaurant']->id,
            'courier_id' => null,
            'restaurant_courier_requested_at' => now(),
            'delivery_address_id' => null,
            'status' => OrderStatus::Ready->value,
            'total_price' => 40,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'customer_name' => 'C',
            'customer_phone' => '0557',
        ]);

        $response = $this->actingAs($firmUser)
            ->getJson(route('firm.orders.poll', ['bootstrap' => '1']))
            ->assertOk()
            ->assertJson([
                'ready_awaiting_courier_count' => 1,
                'courier_request_max_order_id' => $ready->id,
            ]);
        $this->assertGreaterThan(0, (int) ($response->json('courier_request_signal_unix') ?? 0));
    }

    public function test_firm_poll_courier_signal_when_ready_queue_empty_after_assignment(): void
    {
        $this->seedRoles();
        $ctx = $this->makeRestaurantContext();

        $firmUser = User::factory()->create([
            'firm_id' => $ctx['firm']->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
            'restaurant_id' => null,
        ]);

        $courierUser = User::factory()->create([
            'firm_id' => $ctx['firm']->id,
            'role_id' => Role::query()->where('name', Role::COURIER)->value('id'),
        ]);
        $courier = Courier::query()->create([
            'firm_id' => $ctx['firm']->id,
            'user_id' => $courierUser->id,
            'name' => 'K',
            'phone' => '05551112233',
            'vehicle_type' => 'motosiklet',
            'status' => 'active',
        ]);

        Order::query()->create([
            'firm_id' => $ctx['firm']->id,
            'source' => 'phone',
            'user_id' => null,
            'restaurant_id' => $ctx['restaurant']->id,
            'courier_id' => $courier->id,
            'restaurant_courier_requested_at' => now(),
            'delivery_address_id' => null,
            'status' => OrderStatus::CourierAssigned->value,
            'total_price' => 40,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'customer_name' => 'D',
            'customer_phone' => '0558',
        ]);

        $response = $this->actingAs($firmUser)
            ->getJson(route('firm.orders.poll', ['bootstrap' => '1']))
            ->assertOk();

        $this->assertSame(0, (int) $response->json('ready_awaiting_courier_count'));
        $this->assertGreaterThan(0, (int) ($response->json('courier_request_signal_unix') ?? 0));
    }

    public function test_firm_poll_signal_increases_after_courier_declines_assignment(): void
    {
        $this->seedRoles();
        $ctx = $this->makeRestaurantContext();

        $customer = User::factory()->create([
            'firm_id' => $ctx['firm']->id,
            'role_id' => Role::query()->where('name', Role::CUSTOMER)->value('id'),
        ]);
        $address = Address::query()->create([
            'user_id' => $customer->id,
            'title' => 'Ev',
            'address' => 'Test',
            'latitude' => 38.44,
            'longitude' => 27.15,
        ]);

        $courierUser = User::factory()->create([
            'firm_id' => $ctx['firm']->id,
            'role_id' => Role::query()->where('name', Role::COURIER)->value('id'),
        ]);
        $courier = Courier::query()->create([
            'firm_id' => $ctx['firm']->id,
            'user_id' => $courierUser->id,
            'name' => 'K',
            'phone' => '05551112233',
            'vehicle_type' => 'motosiklet',
            'status' => 'active',
        ]);

        $requestedAt = now()->subHour();
        $order = Order::query()->create([
            'firm_id' => $ctx['firm']->id,
            'source' => 'phone',
            'user_id' => $customer->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'courier_id' => $courier->id,
            'restaurant_courier_requested_at' => $requestedAt,
            'delivery_address_id' => $address->id,
            'status' => OrderStatus::CourierAssigned->value,
            'total_price' => 40,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'customer_name' => 'Poll',
            'customer_phone' => '0559',
        ]);

        $firmAdmin = User::factory()->create([
            'firm_id' => $ctx['firm']->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
            'restaurant_id' => null,
        ]);

        $s1 = (int) $this->actingAs($firmAdmin)
            ->getJson(route('firm.orders.poll', ['bootstrap' => 1]))
            ->json('courier_request_signal_unix');

        $this->travel(5)->seconds();

        Sanctum::actingAs($courierUser);
        $this->postJson('/api/v1/courier/orders/'.$order->id.'/decline-assignment', [
            'reason' => 'unavailable',
        ])
            ->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Ready->value, $order->status);
        $this->assertNull($order->courier_id);

        $s2 = (int) $this->actingAs($firmAdmin)
            ->getJson(route('firm.orders.poll', ['bootstrap' => 1]))
            ->json('courier_request_signal_unix');

        $this->assertGreaterThan($s1, $s2);
    }

    private function seedRoles(): void
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
    }

    /**
     * @return array{firm: Firm, restaurant: Restaurant, user: User}
     */
    private function makeRestaurantContext(): array
    {
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
            'name' => 'R',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 38.42,
            'longitude' => 27.13,
        ]);

        $user = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::RESTAURANT)->value('id'),
            'restaurant_id' => $restaurant->id,
        ]);

        return ['firm' => $firm, 'restaurant' => $restaurant, 'user' => $user];
    }
}
