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
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FirmCourierPayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_settlement_attaches_orders_and_void_reverts(): void
    {
        $this->seedRoles();
        $firm = Firm::query()->create([
            'name' => 'F',
            'city' => 'X',
            'district' => 'Y',
            'domain' => 'fp-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
        ]);
        $courierUser = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::COURIER)->value('id'),
        ]);
        $courier = Courier::query()->create([
            'firm_id' => $firm->id,
            'user_id' => $courierUser->id,
            'name' => 'Kurye T',
            'phone' => '555',
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
        $customer = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::CUSTOMER)->value('id'),
        ]);
        $day = now()->toDateString();
        (new Order)->forceFill([
            'firm_id' => $firm->id,
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'courier_id' => $courier->id,
            'delivery_address_id' => null,
            'status' => OrderStatus::Delivered->value,
            'total_price' => 100.00,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'online',
            'platform_fee_amount' => 0,
            'restaurant_commission_amount' => 0,
            'courier_payout_amount' => 50.00,
            'updated_at' => now(),
        ])->save();

        $response = $this->actingAs($admin)->post('/firma/finans/kurye-odemeleri', [
            'courier_id' => $courier->id,
            'preset' => 'today',
            'date_from' => $day,
            'date_to' => $day,
            'include_all_ledger' => 0,
            'payment_method' => 'cash',
            'payment_reference' => 'TEST',
            'notes' => 'unit',
        ]);

        $response->assertRedirect();
        $order = Order::query()->where('courier_id', $courier->id)->first();
        $this->assertNotNull($order?->courier_payout_settlement_id);

        $settlementId = (int) $order->courier_payout_settlement_id;
        $this->actingAs($admin)->post('/firma/finans/kurye-odemeleri/'.$settlementId.'/iptal');

        $order->refresh();
        $this->assertNull($order->courier_payout_settlement_id);
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
}
