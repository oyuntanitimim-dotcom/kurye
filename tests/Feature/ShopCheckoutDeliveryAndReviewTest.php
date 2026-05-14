<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\RestaurantBusinessType;
use App\Models\User;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Review;
use App\Modules\Payments\Models\Payment;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Restaurants\Models\RestaurantCategory;
use App\Modules\Users\Models\Address;
use App\Modules\Users\Models\Role;
use App\Services\Shop\DeliveryFeeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShopCheckoutDeliveryAndReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_uses_firm_default_delivery_fee_from_settings(): void
    {
        $this->seedRoles();
        $ctx = $this->createShopContext(22.5);

        $this->actingAs($ctx['customer'])
            ->post('/alisveris/sepet/ekle', [
                'product_id' => $ctx['product']->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->actingAs($ctx['customer'])
            ->post('/alisveris/odeme', [
                'address_id' => $ctx['address']->id,
                'payment_method' => 'cash_on_delivery',
            ])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $ctx['customer']->id)->latest('id')->firstOrFail();
        $this->assertEquals('22.50', (string) $order->delivery_fee);
        $this->assertEquals('72.50', (string) $order->total_price);
    }

    public function test_checkout_uses_distance_pricing_when_enabled(): void
    {
        $this->seedRoles();
        $ctx = $this->createShopContextWithDistancePricing();

        $expected = (new DeliveryFeeCalculator)->compute(
            $ctx['firm'],
            $ctx['restaurant'],
            $ctx['address']
        );

        $this->actingAs($ctx['customer'])
            ->post('/alisveris/sepet/ekle', [
                'product_id' => $ctx['product']->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->actingAs($ctx['customer'])
            ->post('/alisveris/odeme', [
                'address_id' => $ctx['address']->id,
                'payment_method' => 'cash_on_delivery',
            ])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $ctx['customer']->id)->latest('id')->firstOrFail();
        $this->assertEqualsWithDelta($expected, (float) $order->delivery_fee, 0.01);
        $this->assertNotEquals(99.0, (float) $order->delivery_fee);
    }

    public function test_cart_shows_notice_when_restaurant_has_fixed_shop_delivery_fee(): void
    {
        $this->seedRoles();
        $ctx = $this->createShopContext(20.0);
        $ctx['restaurant']->update(['shop_delivery_fee' => 11.5]);

        $this->actingAs($ctx['customer'])
            ->post('/alisveris/sepet/ekle', [
                'product_id' => $ctx['product']->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->actingAs($ctx['customer'])
            ->get('/alisveris/sepet')
            ->assertOk()
            ->assertSee('sabit', false)
            ->assertSee('11.50', false);
    }

    public function test_restaurant_shop_delivery_fee_overrides_firm_flat_fee(): void
    {
        $this->seedRoles();
        $ctx = $this->createShopContext(22.5);
        $ctx['restaurant']->update(['shop_delivery_fee' => 9.99]);

        $this->actingAs($ctx['customer'])
            ->post('/alisveris/sepet/ekle', [
                'product_id' => $ctx['product']->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->actingAs($ctx['customer'])
            ->post('/alisveris/odeme', [
                'address_id' => $ctx['address']->id,
                'payment_method' => 'cash_on_delivery',
            ])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $ctx['customer']->id)->latest('id')->firstOrFail();
        $this->assertEquals('9.99', (string) $order->delivery_fee);
        $this->assertEquals('59.99', (string) $order->total_price);
    }

    public function test_online_checkout_creates_completed_payment_with_demo_gateway(): void
    {
        $this->seedRoles();
        $ctx = $this->createShopContext(10.0);

        $this->actingAs($ctx['customer'])
            ->post('/alisveris/sepet/ekle', [
                'product_id' => $ctx['product']->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->actingAs($ctx['customer'])
            ->post('/alisveris/odeme', [
                'address_id' => $ctx['address']->id,
                'payment_method' => 'online',
            ])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $ctx['customer']->id)->latest('id')->firstOrFail();
        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame('completed', $payment->status);
        $this->assertSame('local', $payment->provider);
    }

    public function test_customer_can_submit_review_after_delivered(): void
    {
        $this->seedRoles();
        $ctx = $this->createShopContext(15.0);

        $order = Order::query()->create([
            'firm_id' => $ctx['firm']->id,
            'source' => 'own_shop',
            'user_id' => $ctx['customer']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'courier_id' => null,
            'delivery_address_id' => $ctx['address']->id,
            'status' => OrderStatus::Delivered->value,
            'total_price' => 100,
            'delivery_fee' => 15,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
        ]);

        $this->actingAs($ctx['customer'])
            ->post('/alisveris/siparislerim/'.$order->id.'/yorum', [
                'rating' => 4,
                'comment' => 'Güzeldi',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'rating' => 4,
            'comment' => 'Güzeldi',
        ]);
    }

    public function test_cannot_review_twice(): void
    {
        $this->seedRoles();
        $ctx = $this->createShopContext(15.0);

        $order = Order::query()->create([
            'firm_id' => $ctx['firm']->id,
            'source' => 'own_shop',
            'user_id' => $ctx['customer']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'courier_id' => null,
            'delivery_address_id' => $ctx['address']->id,
            'status' => OrderStatus::Delivered->value,
            'total_price' => 100,
            'delivery_fee' => 15,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
        ]);

        Review::query()->create([
            'order_id' => $order->id,
            'rating' => 5,
            'comment' => 'Önce',
        ]);

        $this->actingAs($ctx['customer'])
            ->from('/alisveris/siparislerim/'.$order->id)
            ->post('/alisveris/siparislerim/'.$order->id.'/yorum', [
                'rating' => 2,
            ])
            ->assertSessionHasErrors(['rating']);

        $this->assertSame(5, (int) Review::query()->where('order_id', $order->id)->value('rating'));
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
     * @return array{firm: Firm, restaurant: Restaurant, product: Product, customer: User, address: Address}
     */
    private function createShopContext(float $deliveryFee): array
    {
        $firm = Firm::query()->create([
            'name' => 'Shop Test Firma',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'localhost',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
            'settings' => [
                'default_delivery_fee' => $deliveryFee,
            ],
        ]);

        $restaurant = Restaurant::query()->create([
            'firm_id' => $firm->id,
            'name' => 'Shop Rest',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 38.42,
            'longitude' => 27.13,
        ]);

        $cat = RestaurantCategory::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Menü',
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $cat->id,
            'name' => 'Ürün',
            'price' => 50.00,
            'status' => 'active',
            'stock' => 10,
        ]);

        $customer = User::query()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::CUSTOMER)->value('id'),
            'name' => 'Alıcı',
            'email' => 'buy-'.Str::uuid()->toString().'@example.test',
            'password' => 'password12',
            'status' => 'active',
        ]);

        $address = Address::query()->create([
            'user_id' => $customer->id,
            'title' => 'Ev',
            'address' => 'Cadde 1',
            'latitude' => 38.43,
            'longitude' => 27.14,
        ]);

        return [
            'firm' => $firm,
            'restaurant' => $restaurant,
            'product' => $product,
            'customer' => $customer,
            'address' => $address,
        ];
    }

    /**
     * @return array{firm: Firm, restaurant: Restaurant, product: Product, customer: User, address: Address}
     */
    private function createShopContextWithDistancePricing(): array
    {
        $firm = Firm::query()->create([
            'name' => 'Shop Mesafe Firma',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'localhost',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
            'settings' => [
                'default_delivery_fee' => 99,
                'delivery_use_distance' => true,
                'delivery_distance_base_fee' => 5,
                'delivery_distance_per_km' => 0,
                'delivery_distance_min_fee' => 1,
                'delivery_distance_max_fee' => 500,
            ],
        ]);

        $restaurant = Restaurant::query()->create([
            'firm_id' => $firm->id,
            'name' => 'Shop Rest M',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 38.42,
            'longitude' => 27.13,
        ]);

        $cat = RestaurantCategory::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Menü',
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $cat->id,
            'name' => 'Ürün',
            'price' => 50.00,
            'status' => 'active',
            'stock' => 10,
        ]);

        $customer = User::query()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::CUSTOMER)->value('id'),
            'name' => 'Alıcı M',
            'email' => 'buy-m-'.Str::uuid()->toString().'@example.test',
            'password' => 'password12',
            'status' => 'active',
        ]);

        $address = Address::query()->create([
            'user_id' => $customer->id,
            'title' => 'Ev',
            'address' => 'Cadde 1',
            'latitude' => 38.43,
            'longitude' => 27.14,
        ]);

        return [
            'firm' => $firm,
            'restaurant' => $restaurant,
            'product' => $product,
            'customer' => $customer,
            'address' => $address,
        ];
    }
}
