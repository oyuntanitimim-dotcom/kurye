<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\RestaurantBusinessType;
use App\Jobs\PushMarketplaceOrderStatusJob;
use App\Models\User;
use App\Modules\Integrations\Models\IntegrationConnection;
use App\Modules\Integrations\Models\IntegrationProductMap;
use App\Modules\Integrations\Services\MarketplaceIngestService;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderStateService;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Restaurants\Models\RestaurantCategory;
use App\Modules\Firms\Models\Firm;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class MultiChannelOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_integrations_page_loads(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        $response = $this->actingAs($ctx['restaurantUser'])->get('/restoran/entegrasyon');

        $response->assertOk()->assertSee('Pazar yeri API')->assertSee('Yemeksepeti');
    }

    public function test_restaurant_can_create_manual_order_without_customer_user(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        $response = $this->actingAs($ctx['restaurantUser'])->post('/restoran/siparisler/manuel', [
            'source' => 'phone',
            'customer_name' => 'Test Müşteri',
            'customer_phone' => '05551112233',
            'payment_method' => 'cash_on_delivery',
            'delivery_fee' => '5',
            'notes' => 'Kapıda bırakın',
            'lines' => [
                ['product_id' => $ctx['product']->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'source' => OrderSource::Phone->value,
            'customer_name' => 'Test Müşteri',
            'customer_phone' => '05551112233',
            'restaurant_id' => $ctx['restaurant']->id,
            'user_id' => null,
        ]);
    }

    public function test_marketplace_webhook_creates_order(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        IntegrationConnection::query()->create([
            'firm_id' => $ctx['firm']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'pilot',
            'credentials_encrypted' => null,
            'settings_json' => ['webhook_token' => 'test-token'],
            'is_active' => true,
        ]);

        $payload = [
            'external_order_id' => 'EXT-999',
            'restaurant_id' => $ctx['restaurant']->id,
            'items' => [
                ['product_id' => $ctx['product']->id, 'quantity' => 1],
            ],
            'customer_name' => 'Agg',
            'customer_phone' => '0555',
            'payment_method' => 'cash_on_delivery',
            'delivery_fee' => 0,
        ];

        $response = $this->postJson('/api/v1/integrations/pilot/webhook', $payload, [
            'X-Firm-Id' => (string) $ctx['firm']->id,
            'X-Integration-Token' => 'test-token',
        ]);

        $response->assertOk()->assertJsonPath('ok', true)->assertJsonStructure(['ok', 'request_id', 'order_id']);
        $this->assertDatabaseHas('orders', [
            'source' => OrderSource::Marketplace->value,
            'marketplace_provider' => 'pilot',
        ]);
    }

    public function test_marketplace_webhook_is_idempotent_for_same_external_order_id(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        IntegrationConnection::query()->create([
            'firm_id' => $ctx['firm']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'pilot',
            'credentials_encrypted' => null,
            'settings_json' => ['webhook_token' => 'idem-token'],
            'is_active' => true,
        ]);

        $payload = [
            'external_order_id' => 'EXT-IDEM-1',
            'restaurant_id' => $ctx['restaurant']->id,
            'items' => [
                ['product_id' => $ctx['product']->id, 'quantity' => 1],
            ],
            'customer_name' => 'Idem',
            'customer_phone' => '0555',
            'payment_method' => 'cash_on_delivery',
            'delivery_fee' => 0,
        ];

        $headers = ['X-Integration-Token' => 'idem-token'];

        $first = $this->postJson('/api/v1/integrations/pilot/webhook', $payload, $headers);
        $first->assertOk()->assertJsonPath('ok', true);
        $orderId = $first->json('order_id');

        $second = $this->postJson('/api/v1/integrations/pilot/webhook', $payload, $headers);
        $second->assertOk()->assertJsonPath('ok', true)->assertJsonPath('order_id', $orderId);

        $this->assertSame(1, Order::query()
            ->where('marketplace_provider', 'pilot')
            ->where('firm_id', $ctx['firm']->id)
            ->where('source', OrderSource::Marketplace->value)
            ->count());
    }

    public function test_marketplace_webhook_resolves_external_sku_via_map(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        IntegrationProductMap::query()->create([
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'pilot',
            'external_sku' => 'PLATFORM-SKU-77',
            'product_id' => $ctx['product']->id,
        ]);

        IntegrationConnection::query()->create([
            'firm_id' => $ctx['firm']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'pilot',
            'credentials_encrypted' => null,
            'settings_json' => ['webhook_token' => 'sku-token'],
            'is_active' => true,
        ]);

        $payload = [
            'external_order_id' => 'EXT-SKU-999',
            'items' => [
                ['external_sku' => 'PLATFORM-SKU-77', 'quantity' => 2],
            ],
            'customer_name' => 'Map',
            'customer_phone' => '0555',
            'payment_method' => 'cash_on_delivery',
            'delivery_fee' => 0,
        ];

        $response = $this->postJson('/api/v1/integrations/pilot/webhook', $payload, [
            'X-Integration-Token' => 'sku-token',
        ]);

        $response->assertOk()->assertJsonPath('ok', true);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $ctx['product']->id,
            'quantity' => 2,
        ]);
    }

    public function test_restaurant_can_import_product_maps_via_csv_upload(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        // create empty connection for provider (optional, but UI expects provider exists)
        IntegrationConnection::query()->create([
            'firm_id' => $ctx['firm']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'trendyol_yemek',
            'credentials_encrypted' => null,
            'settings_json' => ['webhook_token' => 'tok'],
            'is_active' => true,
        ]);

        $csv = "\xEF\xBB\xBFexternal_sku,product_id\n317891,{$ctx['product']->id}\n";

        $res = $this->actingAs($ctx['restaurantUser'])->post('/restoran/entegrasyon/trendyol_yemek/urun-eslemesi/import', [
            'file' => UploadedFile::fake()->createWithContent('maps.csv', $csv),
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('integration_product_maps', [
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'trendyol_yemek',
            'external_sku' => '317891',
            'product_id' => $ctx['product']->id,
        ]);
    }

    public function test_restaurant_can_download_product_map_template_csv(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        $res = $this->actingAs($ctx['restaurantUser'])
            ->get('/restoran/entegrasyon/trendyol_yemek/urun-eslemesi/template.csv');

        $res->assertOk();
        $res->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $res->assertSee('external_sku,product_id,product_name,price');
        $res->assertSee((string) $ctx['product']->id);
        $res->assertSee($ctx['product']->name);
    }

    public function test_push_marketplace_status_posts_to_partner_url(): void
    {
        Http::fake([
            'https://partner.test/hook' => Http::response(['ok' => true], 200),
        ]);

        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        IntegrationConnection::query()->create([
            'firm_id' => $ctx['firm']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'pilot',
            'credentials_encrypted' => null,
            'settings_json' => [
                'webhook_token' => 'hook-token',
                'order_status_webhook_url' => 'https://partner.test/hook',
            ],
            'is_active' => true,
        ]);

        $ingest = app(MarketplaceIngestService::class);
        $order = $ingest->ingestFromPayload($ctx['firm']->id, 'pilot', [
            'external_order_id' => 'EXT-HTTP-1',
            'restaurant_id' => $ctx['restaurant']->id,
            'items' => [
                ['product_id' => $ctx['product']->id, 'quantity' => 1],
            ],
            'customer_name' => 'X',
            'customer_phone' => '0',
            'payment_method' => 'cash_on_delivery',
        ]);

        foreach ([OrderStatus::Accepted, OrderStatus::Preparing] as $st) {
            app(OrderStateService::class)->transition($order->fresh(), $st);
            $order = $order->fresh();
        }

        (new PushMarketplaceOrderStatusJob($order->id, OrderStatus::Ready->value))->handle();

        Http::assertSent(function ($request) use ($order): bool {
            return $request->url() === 'https://partner.test/hook'
                && ($request['internal_order_id'] ?? null) === $order->id
                && ($request['status'] ?? null) === OrderStatus::Ready->value;
        });
    }

    public function test_marketplace_status_push_retries_transient_http_errors(): void
    {
        Http::fake([
            'https://partner.test/hook' => Http::sequence()
                ->push(['err' => 1], 503)
                ->push(['err' => 2], 503)
                ->push(['ok' => true], 200),
        ]);

        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        IntegrationConnection::query()->create([
            'firm_id' => $ctx['firm']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'pilot',
            'credentials_encrypted' => null,
            'settings_json' => [
                'webhook_token' => 'retry-token',
                'order_status_webhook_url' => 'https://partner.test/hook',
            ],
            'is_active' => true,
        ]);

        $ingest = app(MarketplaceIngestService::class);
        $order = $ingest->ingestFromPayload($ctx['firm']->id, 'pilot', [
            'external_order_id' => 'EXT-RETRY-HTTP',
            'restaurant_id' => $ctx['restaurant']->id,
            'items' => [
                ['product_id' => $ctx['product']->id, 'quantity' => 1],
            ],
            'customer_name' => 'R',
            'customer_phone' => '0',
            'payment_method' => 'cash_on_delivery',
        ]);

        foreach ([OrderStatus::Accepted, OrderStatus::Preparing] as $st) {
            app(OrderStateService::class)->transition($order->fresh(), $st);
            $order = $order->fresh();
        }

        (new PushMarketplaceOrderStatusJob($order->id, OrderStatus::Ready->value))->handle();

        Http::assertSentCount(3);
    }

    public function test_duplicate_marketplace_status_push_dispatches_single_unique_job(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        $ingest = app(MarketplaceIngestService::class);
        $order = $ingest->ingestFromPayload($ctx['firm']->id, 'pilot', [
            'external_order_id' => 'EXT-UNIQ-1',
            'restaurant_id' => $ctx['restaurant']->id,
            'items' => [
                ['product_id' => $ctx['product']->id, 'quantity' => 1],
            ],
            'customer_name' => 'U',
            'customer_phone' => '0',
            'payment_method' => 'cash_on_delivery',
        ]);

        Queue::fake();

        PushMarketplaceOrderStatusJob::dispatch($order->id, OrderStatus::Ready->value);
        PushMarketplaceOrderStatusJob::dispatch($order->id, OrderStatus::Ready->value);

        Queue::assertPushedTimes(PushMarketplaceOrderStatusJob::class, 1);

        PushMarketplaceOrderStatusJob::dispatch($order->id, OrderStatus::Cancelled->value);
        Queue::assertPushedTimes(PushMarketplaceOrderStatusJob::class, 2);

        Queue::releaseUniqueJobLocks();
    }

    public function test_ready_on_marketplace_order_dispatches_push_job(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantWithProducts();

        $ingest = app(MarketplaceIngestService::class);
        $order = $ingest->ingestFromPayload($ctx['firm']->id, 'pilot', [
            'external_order_id' => 'EXT-1001',
            'restaurant_id' => $ctx['restaurant']->id,
            'items' => [
                ['product_id' => $ctx['product']->id, 'quantity' => 1],
            ],
            'customer_name' => 'X',
            'customer_phone' => '0',
            'payment_method' => 'cash_on_delivery',
        ]);

        foreach ([OrderStatus::Accepted, OrderStatus::Preparing] as $st) {
            app(OrderStateService::class)->transition($order->fresh(), $st);
            $order = $order->fresh();
        }

        Queue::fake();

        app(OrderStateService::class)->transition($order->fresh(), OrderStatus::Ready);

        Queue::assertPushed(PushMarketplaceOrderStatusJob::class, function (PushMarketplaceOrderStatusJob $job) use ($order): bool {
            return $job->orderId === $order->id && $job->status === OrderStatus::Ready->value;
        });
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
