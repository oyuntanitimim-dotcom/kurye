<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\RestaurantBusinessType;
use App\Models\User;
use App\Modules\Firms\Models\Firm;
use App\Modules\Integrations\Models\IntegrationConnection;
use App\Modules\Integrations\Models\IntegrationProductMap;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Restaurants\Models\RestaurantCategory;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class TrendyolGoMealWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_tgo_created_event_payload_creates_order(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        IntegrationProductMap::query()->create([
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'trendyol_yemek',
            'external_sku' => '317891',
            'product_id' => $ctx['product']->id,
        ]);

        IntegrationConnection::query()->create([
            'firm_id' => $ctx['firm']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'trendyol_yemek',
            'credentials_encrypted' => null,
            'settings_json' => ['webhook_token' => 'tgo-token'],
            'is_active' => true,
        ]);

        $payload = [
            'eventType' => 'created',
            'payload' => [
                'orderCode' => '047',
                'customerNote' => 'Servis istiyorum',
                'customer' => [
                    'firstName' => 'Oms',
                    'lastName' => 'M',
                    'phone' => '08502419090',
                ],
                'payment' => [
                    'paymentType' => 'PAY_WITH_CARD',
                ],
                'address' => [
                    'address1' => 'Gündoğdu Koleji Yanı',
                    'address2' => 'İş Adresi',
                    'district' => 'Kadıköy',
                    'city' => 'İstanbul',
                    'latitude' => '36.983687',
                    'longitude' => '35.3272959',
                    'phone' => '08502419090',
                ],
                'lines' => [
                    [
                        'price' => 9.99,
                        'unitSellingPrice' => 9.99,
                        'items' => [
                            [
                                'productId' => 317891,
                                'quantity' => 1,
                                'isCancelled' => false,
                                'name' => 'Coca Cola Zero Sugar (33 cl.)',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $res = $this->postJson('/api/v1/integrations/trendyol_yemek/webhook', $payload, [
            'X-Integration-Token' => 'tgo-token',
        ]);

        $res->assertOk()->assertJsonPath('ok', true);

        $conn = IntegrationConnection::query()
            ->where('restaurant_id', $ctx['restaurant']->id)
            ->where('provider', 'trendyol_yemek')
            ->first();
        $this->assertNotNull($conn);
        $this->assertNotEmpty($conn->settings_json['last_webhook_received_at'] ?? null);
        $this->assertNotEmpty($conn->settings_json['webhook_stats']['total'] ?? null);
        $this->assertSame('created', strtolower((string) ($conn->settings_json['webhook_stats']['last_event_type'] ?? '')));

        $this->assertDatabaseHas('orders', [
            'source' => OrderSource::Marketplace->value,
            'marketplace_provider' => 'trendyol_yemek',
            'restaurant_id' => $ctx['restaurant']->id,
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $ctx['product']->id,
            'quantity' => 1,
        ]);
    }

    public function test_tgo_non_created_event_is_skipped(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        IntegrationConnection::query()->create([
            'firm_id' => $ctx['firm']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'trendyol_yemek',
            'credentials_encrypted' => null,
            'settings_json' => ['webhook_token' => 'tgo-token'],
            'is_active' => true,
        ]);

        $payload = [
            'eventType' => 'shipped',
            'payload' => [
                'orderCode' => '047',
            ],
        ];

        $res = $this->postJson('/api/v1/integrations/trendyol_yemek/webhook', $payload, [
            'X-Integration-Token' => 'tgo-token',
        ]);

        $res->assertOk()->assertJsonPath('ok', true)->assertJsonPath('skipped', true);
    }

    public function test_tgo_delivered_event_updates_existing_order_status(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        IntegrationProductMap::query()->create([
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'trendyol_yemek',
            'external_sku' => '317891',
            'product_id' => $ctx['product']->id,
        ]);

        IntegrationConnection::query()->create([
            'firm_id' => $ctx['firm']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'trendyol_yemek',
            'credentials_encrypted' => null,
            'settings_json' => ['webhook_token' => 'tgo-token'],
            'is_active' => true,
        ]);

        $created = [
            'eventType' => 'created',
            'payload' => [
                'orderCode' => '047',
                'customer' => ['firstName' => 'A', 'lastName' => 'B'],
                'address' => ['address1' => 'X', 'city' => 'Y', 'latitude' => '36.1', 'longitude' => '35.1'],
                'lines' => [
                    ['items' => [['productId' => 317891, 'quantity' => 1, 'isCancelled' => false]]],
                ],
            ],
        ];

        $this->postJson('/api/v1/integrations/trendyol_yemek/webhook', $created, [
            'X-Integration-Token' => 'tgo-token',
        ])->assertOk()->assertJsonPath('ok', true);

        $delivered = [
            'eventType' => 'delivered',
            'payload' => [
                'orderCode' => '047',
            ],
        ];

        $this->postJson('/api/v1/integrations/trendyol_yemek/webhook', $delivered, [
            'X-Integration-Token' => 'tgo-token',
        ])->assertOk()->assertJsonPath('ok', true)->assertJsonPath('updated', true)->assertJsonPath('status', OrderStatus::Delivered->value);
    }

    public function test_tgo_cancelled_event_updates_existing_order_status(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        IntegrationProductMap::query()->create([
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'trendyol_yemek',
            'external_sku' => '317891',
            'product_id' => $ctx['product']->id,
        ]);

        IntegrationConnection::query()->create([
            'firm_id' => $ctx['firm']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'trendyol_yemek',
            'credentials_encrypted' => null,
            'settings_json' => ['webhook_token' => 'tgo-token'],
            'is_active' => true,
        ]);

        $created = [
            'eventType' => 'created',
            'payload' => [
                'orderCode' => '047',
                'customer' => ['firstName' => 'A', 'lastName' => 'B'],
                'address' => ['address1' => 'X', 'city' => 'Y', 'latitude' => '36.1', 'longitude' => '35.1'],
                'lines' => [
                    ['items' => [['productId' => 317891, 'quantity' => 1, 'isCancelled' => false]]],
                ],
            ],
        ];

        $this->postJson('/api/v1/integrations/trendyol_yemek/webhook', $created, [
            'X-Integration-Token' => 'tgo-token',
        ])->assertOk()->assertJsonPath('ok', true);

        $cancelled = [
            'eventType' => 'cancelled',
            'payload' => [
                'orderCode' => '047',
            ],
        ];

        $this->postJson('/api/v1/integrations/trendyol_yemek/webhook', $cancelled, [
            'X-Integration-Token' => 'tgo-token',
        ])->assertOk()->assertJsonPath('ok', true)->assertJsonPath('updated', true)->assertJsonPath('status', OrderStatus::Cancelled->value);
    }

    public function test_tgo_shipped_event_does_not_regress_after_delivered(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        IntegrationProductMap::query()->create([
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'trendyol_yemek',
            'external_sku' => '317891',
            'product_id' => $ctx['product']->id,
        ]);

        IntegrationConnection::query()->create([
            'firm_id' => $ctx['firm']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'trendyol_yemek',
            'credentials_encrypted' => null,
            'settings_json' => ['webhook_token' => 'tgo-token'],
            'is_active' => true,
        ]);

        $created = [
            'eventType' => 'created',
            'payload' => [
                'orderCode' => '047',
                'customer' => ['firstName' => 'A', 'lastName' => 'B'],
                'address' => ['address1' => 'X', 'city' => 'Y', 'latitude' => '36.1', 'longitude' => '35.1'],
                'lines' => [
                    ['items' => [['productId' => 317891, 'quantity' => 1, 'isCancelled' => false]]],
                ],
            ],
        ];

        $this->postJson('/api/v1/integrations/trendyol_yemek/webhook', $created, [
            'X-Integration-Token' => 'tgo-token',
        ])->assertOk()->assertJsonPath('ok', true);

        $delivered = [
            'eventType' => 'delivered',
            'payload' => [
                'orderCode' => '047',
            ],
        ];
        $this->postJson('/api/v1/integrations/trendyol_yemek/webhook', $delivered, [
            'X-Integration-Token' => 'tgo-token',
        ])->assertOk()->assertJsonPath('updated', true)->assertJsonPath('status', OrderStatus::Delivered->value);

        $shippedLate = [
            'eventType' => 'shipped',
            'payload' => [
                'orderCode' => '047',
            ],
        ];

        $this->postJson('/api/v1/integrations/trendyol_yemek/webhook', $shippedLate, [
            'X-Integration-Token' => 'tgo-token',
        ])->assertOk()->assertJsonPath('skipped', true)->assertJsonPath('reason', 'terminal_status');
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
     * @return array{firm: Firm, restaurant: Restaurant, restaurantUser: User, product: Product}
     */
    private function createRestaurantWithProducts(): array
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
            'name' => 'Restoran',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 38.42,
            'longitude' => 27.13,
        ]);

        $restaurantUser = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::RESTAURANT)->value('id'),
            'restaurant_id' => $restaurant->id,
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

        return [
            'firm' => $firm,
            'restaurant' => $restaurant,
            'restaurantUser' => $restaurantUser,
            'product' => $product,
        ];
    }
}

