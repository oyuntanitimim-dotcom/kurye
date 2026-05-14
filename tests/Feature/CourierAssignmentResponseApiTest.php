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

class CourierAssignmentResponseApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        foreach (
            [Role::FIRM_ADMIN, Role::COURIER, Role::CUSTOMER] as $name
        ) {
            Role::query()->firstOrCreate(['name' => $name]);
        }
    }

    /** @return array{firm: Firm, courierUser: User, courier: Courier, order: Order} */
    private function firmAssignedOrder(): array
    {
        $firm = Firm::query()->create([
            'name' => 'F',
            'city' => 'c',
            'district' => 'd',
            'domain' => 'f-'.Str::uuid()->toString().'.local',
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

        $courierUser = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::COURIER)->value('id'),
        ]);

        $courier = Courier::query()->create([
            'firm_id' => $firm->id,
            'user_id' => $courierUser->id,
            'name' => 'K',
            'phone' => '05001112233',
            'vehicle_type' => 'motosiklet',
            'status' => 'active',
        ]);

        $order = Order::query()->create([
            'firm_id' => $firm->id,
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'courier_id' => $courier->id,
            'delivery_address_id' => $address->id,
            'status' => OrderStatus::CourierAssigned->value,
            'total_price' => 100,
            'delivery_fee' => 10,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'restaurant_courier_requested_at' => now(),
        ]);

        return ['firm' => $firm, 'courierUser' => $courierUser, 'courier' => $courier, 'order' => $order];
    }

    public function test_courier_accepts_assignment_via_api(): void
    {
        $this->seedRoles();
        $ctx = $this->firmAssignedOrder();
        $token = $ctx['courierUser']->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/courier/orders/'.$ctx['order']->id.'/accept-assignment')
            ->assertOk();

        $this->assertSame(OrderStatus::CourierAccepted->value, $ctx['order']->fresh()->status);
    }

    public function test_courier_declines_assignment_via_api(): void
    {
        $this->seedRoles();
        $ctx = $this->firmAssignedOrder();
        $token = $ctx['courierUser']->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/courier/orders/'.$ctx['order']->id.'/decline-assignment', [
                'reason' => 'unavailable',
            ])
            ->assertOk();

        $o = $ctx['order']->fresh();
        $this->assertSame(OrderStatus::Ready->value, $o->status);
        $this->assertNull($o->courier_id);
    }

    public function test_patch_picked_up_requires_acceptance_first(): void
    {
        $this->seedRoles();
        $ctx = $this->firmAssignedOrder();
        $token = $ctx['courierUser']->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/courier/orders/'.$ctx['order']->id, [
                'status' => 'picked_up',
            ])
            ->assertStatus(422);
    }
}
