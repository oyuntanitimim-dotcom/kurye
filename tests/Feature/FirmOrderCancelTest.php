<?php

declare(strict_types=1);

namespace Tests\Feature;

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
use Tests\TestCase;

class FirmOrderCancelTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        foreach (
            [Role::SUPER_ADMIN, Role::FIRM_ADMIN, Role::RESTAURANT, Role::COURIER, Role::CUSTOMER] as $name
        ) {
            Role::query()->firstOrCreate(['name' => $name]);
        }
    }

    /**
     * @return array{admin: User, order: Order, otherFirmOrder: Order}
     */
    private function firmAdminWithReadyAndPendingOrders(): array
    {
        $firm = Firm::query()->create([
            'name' => 'A Firma',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'a-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);
        $otherFirm = Firm::query()->create([
            'name' => 'B Firma',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'b-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
        ]);

        $restaurant = Restaurant::query()->create([
            'firm_id' => $firm->id,
            'name' => 'R1',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 38.42,
            'longitude' => 27.13,
        ]);

        $customer = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::CUSTOMER)->value('id'),
        ]);
        $address = Address::query()->create([
            'user_id' => $customer->id,
            'title' => 'Ev',
            'address' => 'A',
            'latitude' => 38.44,
            'longitude' => 27.15,
        ]);

        $ready = Order::query()->create([
            'firm_id' => $firm->id,
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'courier_id' => null,
            'delivery_address_id' => $address->id,
            'status' => OrderStatus::Ready->value,
            'total_price' => 100,
            'delivery_fee' => 10,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'restaurant_courier_requested_at' => now(),
        ]);

        $otherR = Restaurant::query()->create([
            'firm_id' => $otherFirm->id,
            'name' => 'R2',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 38.42,
            'longitude' => 27.13,
        ]);
        $otherC = User::factory()->create([
            'firm_id' => $otherFirm->id,
            'role_id' => Role::query()->where('name', Role::CUSTOMER)->value('id'),
        ]);
        $otherAddr = Address::query()->create([
            'user_id' => $otherC->id,
            'title' => 'Ev',
            'address' => 'B',
            'latitude' => 38.44,
            'longitude' => 27.15,
        ]);
        $otherOrder = Order::query()->create([
            'firm_id' => $otherFirm->id,
            'user_id' => $otherC->id,
            'restaurant_id' => $otherR->id,
            'courier_id' => null,
            'delivery_address_id' => $otherAddr->id,
            'status' => OrderStatus::Ready->value,
            'total_price' => 100,
            'delivery_fee' => 10,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
        ]);

        return ['admin' => $admin, 'order' => $ready, 'otherFirmOrder' => $otherOrder];
    }

    public function test_firm_admin_can_cancel_ready_order(): void
    {
        $this->seedRoles();
        $ctx = $this->firmAdminWithReadyAndPendingOrders();
        $order = $ctx['order'];

        $this->actingAs($ctx['admin'])
            ->from(route('firm.dashboard'))
            ->post(route('firm.orders.cancel', $order))
            ->assertRedirect();
        $this->assertSame(OrderStatus::Cancelled->value, $order->fresh()->status);
    }

    public function test_firm_cannot_cancel_order_of_another_firm(): void
    {
        $this->seedRoles();
        $ctx = $this->firmAdminWithReadyAndPendingOrders();

        $this->actingAs($ctx['admin'])
            ->post(route('firm.orders.cancel', $ctx['otherFirmOrder']))
            ->assertForbidden();
    }

    public function test_firm_cannot_cancel_delivered_order(): void
    {
        $this->seedRoles();
        $ctx = $this->firmAdminWithReadyAndPendingOrders();
        $order = $ctx['order'];
        $order->update(['status' => OrderStatus::Delivered->value]);

        $this->actingAs($ctx['admin'])
            ->from(route('firm.dashboard'))
            ->post(route('firm.orders.cancel', $order))
            ->assertRedirect();
        $this->assertSame(OrderStatus::Delivered->value, $order->fresh()->status);
    }

    public function test_dashboard_does_not_list_pre_ready_statuses_in_recent(): void
    {
        $this->seedRoles();
        $ctx = $this->firmAdminWithReadyAndPendingOrders();
        $firmId = (int) $ctx['admin']->firm_id;
        $restaurant = Restaurant::query()->where('firm_id', $firmId)->firstOrFail();
        $customer = User::query()->where('firm_id', $firmId)->where('role_id', Role::query()->where('name', Role::CUSTOMER)->value('id'))->firstOrFail();
        $address = Address::query()->where('user_id', $customer->id)->firstOrFail();

        Order::query()->create([
            'firm_id' => $firmId,
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'courier_id' => null,
            'delivery_address_id' => $address->id,
            'status' => OrderStatus::Preparing->value,
            'total_price' => 50,
            'delivery_fee' => 5,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
        ]);
        $lastPreparing = Order::query()->latest('id')->first();
        $this->assertNotNull($lastPreparing);
        $this->assertSame(OrderStatus::Preparing->value, $lastPreparing->status);

        $r = $this->actingAs($ctx['admin'])->get(route('firm.dashboard'));
        $r->assertOk();
        $r->assertDontSee('siparisler/'.$lastPreparing->id, false);
    }
}
