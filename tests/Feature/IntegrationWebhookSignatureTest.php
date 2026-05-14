<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\RestaurantBusinessType;
use App\Models\User;
use App\Modules\Firms\Models\Firm;
use App\Modules\Integrations\Models\IntegrationConnection;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Restaurants\Models\RestaurantCategory;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class IntegrationWebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_rejects_oversized_payload(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantContext();

        IntegrationConnection::query()->create([
            'firm_id' => $ctx['firm']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'pilot',
            'credentials_encrypted' => null,
            'settings_json' => ['webhook_token' => 'tok-body'],
            'is_active' => true,
        ]);

        Config::set('marketplace_integrations.webhook_max_body_bytes', 128);
        $json = json_encode(['pad' => str_repeat('Z', 400)], JSON_THROW_ON_ERROR);
        $this->assertGreaterThan(128, strlen($json));

        $response = $this->call('POST', '/api/v1/integrations/pilot/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_INTEGRATION_TOKEN' => 'tok-body',
        ], $json);

        $response->assertStatus(413);
    }

    public function test_webhook_rejects_when_secret_configured_but_signature_missing(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantContext();

        IntegrationConnection::query()->create([
            'firm_id' => $ctx['firm']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'pilot',
            'credentials_encrypted' => null,
            'settings_json' => [
                'webhook_token' => 'tok',
                'webhook_secret' => 'secret-a',
            ],
            'is_active' => true,
        ]);

        $payload = $this->minimalPayload($ctx);
        $response = $this->postJson('/api/v1/integrations/pilot/webhook', $payload, [
            'X-Integration-Token' => 'tok',
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantContext();

        IntegrationConnection::query()->create([
            'firm_id' => $ctx['firm']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'pilot',
            'credentials_encrypted' => null,
            'settings_json' => [
                'webhook_token' => 'tok',
                'webhook_secret' => 'secret-a',
            ],
            'is_active' => true,
        ]);

        $payload = $this->minimalPayload($ctx);
        $json = json_encode($payload, JSON_THROW_ON_ERROR);

        $response = $this->call('POST', '/api/v1/integrations/pilot/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_INTEGRATION_TOKEN' => 'tok',
            'HTTP_X_INTEGRATION_SIGNATURE' => 'sha256='.hash_hmac('sha256', $json, 'wrong-secret'),
        ], $json);

        $response->assertStatus(401);
    }

    public function test_webhook_accepts_valid_hmac_sha256_signature(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantContext();

        IntegrationConnection::query()->create([
            'firm_id' => $ctx['firm']->id,
            'restaurant_id' => $ctx['restaurant']->id,
            'provider' => 'pilot',
            'credentials_encrypted' => null,
            'settings_json' => [
                'webhook_token' => 'tok',
                'webhook_secret' => 'secret-a',
            ],
            'is_active' => true,
        ]);

        $payload = $this->minimalPayload($ctx);
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $sig = hash_hmac('sha256', $json, 'secret-a');

        $response = $this->call('POST', '/api/v1/integrations/pilot/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_INTEGRATION_TOKEN' => 'tok',
            'HTTP_X_INTEGRATION_SIGNATURE' => $sig,
        ], $json);

        $response->assertOk()->assertJsonPath('ok', true);
        $this->assertDatabaseHas('orders', [
            'source' => OrderSource::Marketplace->value,
            'marketplace_provider' => 'pilot',
        ]);
    }

    public function test_restaurant_panel_can_set_and_clear_webhook_secret(): void
    {
        $this->seedRoles();
        $ctx = $this->createRestaurantContext();

        $this->actingAs($ctx['user'])->put('/restoran/entegrasyon/pilot', [
            'is_active' => '1',
            'webhook_secret' => 'panel-secret-99',
        ]);

        $conn = IntegrationConnection::query()->where('restaurant_id', $ctx['restaurant']->id)->where('provider', 'pilot')->first();
        $this->assertNotNull($conn);
        $this->assertSame('panel-secret-99', $conn->settings_json['webhook_secret'] ?? null);

        $this->actingAs($ctx['user'])->put('/restoran/entegrasyon/pilot', [
            'is_active' => '1',
            'webhook_secret_clear' => '1',
        ]);

        $conn->refresh();
        $this->assertArrayNotHasKey('webhook_secret', $conn->settings_json ?? []);
    }

    /**
     * @return array{firm: Firm, restaurant: Restaurant, user: User, product: Product}
     */
    private function createRestaurantContext(): array
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

        $user = User::factory()->create([
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
            'user' => $user,
            'product' => $product,
        ];
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
     * @param  array{firm: Firm, restaurant: Restaurant, user: User, product: Product}  $ctx
     * @return array<string, mixed>
     */
    private function minimalPayload(array $ctx): array
    {
        return [
            'external_order_id' => 'EXT-SIG-'.Str::random(6),
            'restaurant_id' => $ctx['restaurant']->id,
            'items' => [
                ['product_id' => $ctx['product']->id, 'quantity' => 1],
            ],
            'customer_name' => 'Sig',
            'customer_phone' => '0555',
            'payment_method' => 'cash_on_delivery',
            'delivery_fee' => 0,
        ];
    }
}
