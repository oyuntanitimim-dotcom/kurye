<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\RestaurantBusinessType;
use App\Models\User;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Couriers\Models\CourierLocation;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderStatusHistory;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Address;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FirmOperationsAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_snapshot_requires_authentication(): void
    {
        $response = $this->get('/firma/operasyon/ozet');

        $response->assertRedirect();
    }

    public function test_operations_snapshot_returns_json_for_firm_admin(): void
    {
        $this->seedRoles();
        $ctx = $this->createFirmWithOrder();

        $response = $this->actingAs($ctx['admin'])->getJson('/firma/operasyon/ozet');

        $response->assertOk()
            ->assertJsonStructure([
                'generated_at',
                'geo_redis_enabled',
                'firm',
                'orders',
                'couriers',
            ]);

        $ids = collect($response->json('orders'))->pluck('id')->all();
        $this->assertContains($ctx['order']->id, $ids);
    }

    public function test_operations_snapshot_only_includes_same_firm_orders(): void
    {
        $this->seedRoles();
        $a = $this->createFirmWithOrder();
        $b = $this->createFirmWithOrder();

        $response = $this->actingAs($a['admin'])->getJson('/firma/operasyon/ozet');

        $response->assertOk();
        $ids = collect($response->json('orders'))->pluck('id')->all();
        $this->assertContains($a['order']->id, $ids);
        $this->assertNotContains($b['order']->id, $ids);
    }

    public function test_firm_operations_page_loads_for_admin(): void
    {
        $this->seedRoles();
        $ctx = $this->createFirmWithOrder();

        $response = $this->actingAs($ctx['admin'])->get('/firma/operasyon');

        $response->assertOk();
        $response->assertSee('Operasyon', false);
        $response->assertSee('Yenile', false);
        $response->assertSee('OpenStreetMap', false);
        $response->assertSee('op-map', false);
        $response->assertSee('Esc', false);
        $response->assertSee('leaflet', false);
    }

    public function test_non_firm_admin_cannot_access_snapshot(): void
    {
        $this->seedRoles();
        $firm = Firm::query()->create([
            'name' => 'Test',
            'city' => 'X',
            'district' => 'Y',
            'domain' => 'cust-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);
        $customer = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::CUSTOMER)->value('id'),
        ]);

        $response = $this->actingAs($customer)->get('/firma/operasyon/ozet');

        $response->assertRedirect();
    }

    public function test_firm_admin_can_assign_courier_to_ready_order(): void
    {
        $this->seedRoles();
        $ctx = $this->createFirmWithOrder(OrderStatus::Ready);

        $response = $this->actingAs($ctx['admin'])->from('/firma/operasyon')->post(
            '/firma/siparisler/'.$ctx['order']->id.'/kurye',
            ['courier_id' => $ctx['courier']->id]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'id' => $ctx['order']->id,
            'courier_id' => $ctx['courier']->id,
            'status' => OrderStatus::CourierAssigned->value,
        ]);
    }

    public function test_assign_courier_forbidden_for_other_firm_order(): void
    {
        $this->seedRoles();
        $firmA = $this->createFirmWithOrder(OrderStatus::Ready);
        $firmB = $this->createFirmWithOrder(OrderStatus::Ready);

        $response = $this->actingAs($firmA['admin'])->post(
            '/firma/siparisler/'.$firmB['order']->id.'/kurye',
            ['courier_id' => $firmB['courier']->id]
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('orders', [
            'id' => $firmB['order']->id,
            'courier_id' => null,
        ]);
    }

    public function test_firm_admin_can_reassign_courier_on_active_delivery(): void
    {
        $this->seedRoles();
        $ctx = $this->createFirmWithOrder(
            OrderStatus::CourierAssigned,
            [],
            2,
            true
        );

        $response = $this->actingAs($ctx['admin'])->from('/firma/operasyon')->post(
            '/firma/siparisler/'.$ctx['order']->id.'/kurye',
            ['courier_id' => $ctx['courier2']->id]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'id' => $ctx['order']->id,
            'courier_id' => $ctx['courier2']->id,
            'status' => OrderStatus::CourierAssigned->value,
        ]);

        $history = OrderStatusHistory::query()
            ->where('order_id', $ctx['order']->id)
            ->where('status', OrderStatus::CourierAssigned->value)
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($history);
        $this->assertSame('courier_reassigned', $history->meta['event'] ?? null);
        $this->assertSame($ctx['courier']->id, $history->meta['from_courier_id'] ?? null);
        $this->assertSame($ctx['courier2']->id, $history->meta['to_courier_id'] ?? null);
    }

    public function test_assign_courier_not_found_when_courier_belongs_to_another_firm(): void
    {
        $this->seedRoles();
        $firmA = $this->createFirmWithOrder(OrderStatus::Ready);
        $firmB = $this->createFirmWithOrder(OrderStatus::Ready);

        $response = $this->actingAs($firmA['admin'])->post(
            '/firma/siparisler/'.$firmA['order']->id.'/kurye',
            ['courier_id' => $firmB['courier']->id]
        );

        $response->assertNotFound();
        $this->assertDatabaseHas('orders', [
            'id' => $firmA['order']->id,
            'courier_id' => null,
        ]);
    }

    public function test_assign_courier_validation_fails_without_courier_id(): void
    {
        $this->seedRoles();
        $ctx = $this->createFirmWithOrder(OrderStatus::Ready);

        $response = $this->actingAs($ctx['admin'])->post(
            '/firma/siparisler/'.$ctx['order']->id.'/kurye',
            []
        );

        $response->assertSessionHasErrors('courier_id');
    }

    public function test_manual_auto_dispatch_uses_load_fallback_when_no_courier_gps(): void
    {
        $this->seedRoles();
        $ctx = $this->createFirmWithOrder(OrderStatus::Ready, [], 2);
        $firm = Firm::query()->findOrFail($ctx['admin']->firm_id);
        $firm->update([
            'settings' => [
                'auto_dispatch_enabled' => true,
                'location_max_age_minutes' => 60,
            ],
        ]);

        $response = $this->actingAs($ctx['admin'])->from('/firma/siparisler')->post(
            route('firm.orders.auto_dispatch', $ctx['order'])
        );

        $response->assertRedirect();
        $expectedCourierId = min((int) $ctx['courier']->id, (int) $ctx['courier2']->id);
        $this->assertDatabaseHas('orders', [
            'id' => $ctx['order']->id,
            'courier_id' => $expectedCourierId,
            'status' => OrderStatus::CourierAssigned->value,
        ]);
        $this->assertDatabaseHas('order_dispatch_decisions', [
            'order_id' => $ctx['order']->id,
            'chosen_courier_id' => $expectedCourierId,
            'trigger' => 'manual_ui',
        ]);
    }

    public function test_manual_auto_dispatch_assigns_nearest_courier(): void
    {
        $this->seedRoles();
        $ctx = $this->createFirmWithOrder(OrderStatus::Ready, [], 2);
        $firm = Firm::query()->findOrFail($ctx['admin']->firm_id);
        $firm->update([
            'settings' => [
                'auto_dispatch_enabled' => true,
                'location_max_age_minutes' => 60,
            ],
        ]);

        CourierLocation::query()->create([
            'courier_id' => $ctx['courier']->id,
            'latitude' => 38.42,
            'longitude' => 27.13,
            'updated_at' => now(),
        ]);
        CourierLocation::query()->create([
            'courier_id' => $ctx['courier2']->id,
            'latitude' => 41.0,
            'longitude' => 29.0,
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($ctx['admin'])->from('/firma/operasyon')->post(
            route('firm.orders.auto_dispatch', $ctx['order'])
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'id' => $ctx['order']->id,
            'courier_id' => $ctx['courier']->id,
            'status' => OrderStatus::CourierAssigned->value,
        ]);
        $this->assertDatabaseHas('order_dispatch_decisions', [
            'order_id' => $ctx['order']->id,
            'chosen_courier_id' => $ctx['courier']->id,
            'trigger' => 'manual_ui',
        ]);
    }

    public function test_public_tracking_page_ok_with_valid_token(): void
    {
        $this->seedRoles();
        $ctx = $this->createFirmWithOrder();

        $token = $ctx['order']->fresh()->tracking_token;
        $this->assertNotNull($token);

        $response = $this->get('/takip/'.$token);
        $response->assertOk();
        $response->assertSee('Sipariş takibi', false);
    }

    public function test_public_tracking_page_not_found_for_bad_token(): void
    {
        $response = $this->get('/takip/'.str_repeat('x', 48));

        $response->assertNotFound();
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
     * @param  array<string, mixed>  $orderOverrides
     * @return array{admin: User, order: Order, courier: Courier, courier2?: Courier}
     */
    private function createFirmWithOrder(
        ?OrderStatus $status = null,
        array $orderOverrides = [],
        int $courierCount = 1,
        bool $attachFirstCourierToOrder = false,
    ): array {
        $status ??= OrderStatus::Pending;
        $courierCount = max(1, $courierCount);

        $firm = Firm::query()->create([
            'name' => 'Test Firma',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'firm-'.Str::uuid()->toString().'.local',
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
            'name' => 'Restoran',
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
            'address' => 'Test adres',
            'latitude' => 38.44,
            'longitude' => 27.15,
        ]);

        $couriers = [];
        for ($i = 0; $i < $courierCount; $i++) {
            $courierUser = User::factory()->create([
                'firm_id' => $firm->id,
                'role_id' => Role::query()->where('name', Role::COURIER)->value('id'),
            ]);
            $couriers[] = Courier::query()->create([
                'firm_id' => $firm->id,
                'user_id' => $courierUser->id,
                'name' => 'Kurye Test '.($i + 1),
                'phone' => sprintf('0555000%04d', $i + 100),
                'vehicle_type' => 'motosiklet',
                'status' => 'active',
            ]);
        }

        $courierId = $orderOverrides['courier_id'] ?? null;
        if ($attachFirstCourierToOrder && $courierId === null) {
            $courierId = $couriers[0]->id;
        }

        $baseOrder = [
            'firm_id' => $firm->id,
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'courier_id' => $courierId,
            'delivery_address_id' => $address->id,
            'status' => $status->value,
            'total_price' => 100.00,
            'delivery_fee' => 10.00,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'notes' => null,
        ];
        if (
            $status === OrderStatus::Pending
            && $courierId === null
            && ! array_key_exists('restaurant_courier_requested_at', $orderOverrides)
        ) {
            // Snapshot’ta görünsün: restoran kuryeyi bekleyen bekleyenler (firm operasyon)
            $baseOrder['restaurant_courier_requested_at'] = now();
        }

        if (
            $status === OrderStatus::Ready
            && $courierId === null
            && ! array_key_exists('restaurant_courier_requested_at', $orderOverrides)
        ) {
            $baseOrder['restaurant_courier_requested_at'] = now();
        }

        $order = Order::query()->create(array_merge($baseOrder, $orderOverrides));

        $out = [
            'admin' => $admin,
            'order' => $order,
            'courier' => $couriers[0],
        ];
        if (isset($couriers[1])) {
            $out['courier2'] = $couriers[1];
        }

        return $out;
    }
}
