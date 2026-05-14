<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\RestaurantBusinessType;
use App\Models\User;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Restaurants\Models\RestaurantCategory;
use App\Modules\Users\Models\Address;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Müşteri: mağaza sepeti → ödeme → sipariş.
 * İşletme: onay → hazırlanıyor → hazır.
 * Kurye şirketi: kurye ata.
 * Kurye: alındı → yolda → teslim (snapshot).
 */
class CustomerOrderToDeliveryE2ETest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_checkout_through_restaurant_and_courier_to_delivered(): void
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
            'name' => 'E2E Kurye',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'localhost',
            'platform_fee_per_order' => 2.5,
            'default_restaurant_fee_per_delivery' => 3.5,
            'status' => 'active',
        ]);

        $restaurant = Restaurant::query()->create([
            'firm_id' => $firm->id,
            'name' => 'E2E Restoran',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 38.42,
            'longitude' => 27.13,
        ]);

        $category = RestaurantCategory::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Menü',
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => 'Test Ürün',
            'description' => null,
            'price' => 80.00,
            'status' => 'active',
            'stock' => 99,
        ]);

        $restaurantUser = User::query()->create([
            'firm_id' => $firm->id,
            'restaurant_id' => $restaurant->id,
            'role_id' => Role::query()->where('name', Role::RESTAURANT)->value('id'),
            'name' => 'E2E Restoran Yetkili',
            'email' => 'e2e-rest-'.Str::uuid()->toString().'@example.test',
            'password' => 'password12',
            'status' => 'active',
        ]);

        $firmAdmin = User::query()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
            'name' => 'E2E Firma Admin',
            'email' => 'e2e-firm-'.Str::uuid()->toString().'@example.test',
            'password' => 'password12',
            'status' => 'active',
        ]);

        $courierUser = User::query()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::COURIER)->value('id'),
            'name' => 'E2E Kurye',
            'email' => 'e2e-courier-'.Str::uuid()->toString().'@example.test',
            'password' => 'password12',
            'status' => 'active',
        ]);

        $courier = Courier::query()->create([
            'firm_id' => $firm->id,
            'user_id' => $courierUser->id,
            'name' => 'E2E Kurye',
            'phone' => '05551112233',
            'vehicle_type' => 'motosiklet',
            'status' => 'active',
            'compensation_type' => 'per_delivery',
            'compensation_per_delivery' => 25.00,
        ]);

        $customer = User::query()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::CUSTOMER)->value('id'),
            'name' => 'E2E Müşteri',
            'email' => 'e2e-cust-'.Str::uuid()->toString().'@example.test',
            'password' => 'password12',
            'status' => 'active',
        ]);

        $address = Address::query()->create([
            'user_id' => $customer->id,
            'title' => 'Ev',
            'address' => 'Test cad. No:1',
            'latitude' => 38.44,
            'longitude' => 27.15,
        ]);

        $this->actingAs($customer)
            ->post('/alisveris/sepet/ekle', [
                'product_id' => $product->id,
                'quantity' => 2,
            ])
            ->assertRedirect();

        $checkout = $this->actingAs($customer)->post('/alisveris/odeme', [
            'address_id' => $address->id,
            'payment_method' => 'cash_on_delivery',
            'notes' => 'E2E test',
        ]);

        $checkout->assertRedirect();
        $order = Order::query()->where('user_id', $customer->id)->latest('id')->firstOrFail();
        $this->assertSame(OrderStatus::Pending->value, $order->status);
        $this->assertSame((int) $restaurant->id, (int) $order->restaurant_id);

        $this->actingAs($restaurantUser)
            ->post(route('restaurant.orders.accept', $order))
            ->assertRedirect();
        $order->refresh();
        $this->assertSame(OrderStatus::Accepted->value, $order->status);

        $this->actingAs($restaurantUser)
            ->post(route('restaurant.orders.preparing', $order))
            ->assertRedirect();
        $order->refresh();
        $this->assertSame(OrderStatus::Preparing->value, $order->status);

        $this->actingAs($restaurantUser)
            ->post(route('restaurant.orders.ready', $order))
            ->assertRedirect();
        $order->refresh();
        $this->assertSame(OrderStatus::Ready->value, $order->status);

        $this->actingAs($restaurantUser)
            ->post(route('restaurant.orders.request_courier', $order))
            ->assertRedirect();
        $order->refresh();
        $this->assertNotNull($order->restaurant_courier_requested_at);

        $this->actingAs($firmAdmin)
            ->from('/firma/operasyon')
            ->post(route('firm.orders.assign', $order), ['courier_id' => $courier->id])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(OrderStatus::CourierAssigned->value, $order->status);
        $this->assertSame((int) $courier->id, (int) $order->courier_id);

        $this->actingAs($courierUser)
            ->post(route('courier.orders.accept_firm_assignment', $order))
            ->assertRedirect();
        $order->refresh();
        $this->assertSame(OrderStatus::CourierAccepted->value, $order->status);

        $this->actingAs($courierUser)
            ->post(route('courier.orders.picked_up', $order))
            ->assertRedirect();
        $order->refresh();
        $this->assertSame(OrderStatus::PickedUp->value, $order->status);

        $this->actingAs($courierUser)
            ->post(route('courier.orders.on_the_way', $order))
            ->assertRedirect();
        $order->refresh();
        $this->assertSame(OrderStatus::OnTheWay->value, $order->status);

        $this->actingAs($courierUser)
            ->post(route('courier.orders.delivered', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered->value, $order->status);
        $this->assertEquals('2.50', (string) $order->platform_fee_amount);
        $this->assertEquals('3.50', (string) $order->restaurant_commission_amount);
        $this->assertEquals('25.00', (string) $order->courier_payout_amount);
        $this->assertNotNull($order->tracking_revoked_at);
    }
}
