<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\RestaurantBusinessType;
use App\Models\User;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FirmFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_firm_finance_overview_requires_firm_admin(): void
    {
        $this->seedRoles();
        $firm = Firm::query()->create([
            'name' => 'F',
            'city' => 'X',
            'district' => 'Y',
            'domain' => 'f-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);
        $customer = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::CUSTOMER)->value('id'),
        ]);

        $response = $this->actingAs($customer)->get('/firma/finans/genel-durum');

        $response->assertRedirect();
    }

    public function test_firm_finance_overview_ok_for_firm_admin(): void
    {
        $this->seedRoles();
        $firm = Firm::query()->create([
            'name' => 'F',
            'city' => 'X',
            'district' => 'Y',
            'domain' => 'f-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 1,
            'default_restaurant_fee_per_delivery' => 5,
            'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
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
        (new Order)->forceFill([
            'firm_id' => $firm->id,
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'courier_id' => null,
            'delivery_address_id' => null,
            'status' => OrderStatus::Delivered->value,
            'total_price' => 100.00,
            'delivery_fee' => 0,
            'discount_amount' => 0,
            'payment_method' => 'online',
            'platform_fee_amount' => 1,
            'restaurant_commission_amount' => 5,
            'courier_payout_amount' => null,
        ])->save();

        $response = $this->actingAs($admin)->get('/firma/finans/genel-durum');

        $response->assertOk()
            ->assertSee('Genel durum', false)
            ->assertSee('Platform paket ücreti', false)
            ->assertSee('1.00', false);
    }

    public function test_reconciliation_csv_export(): void
    {
        $this->seedRoles();
        $firm = Firm::query()->create([
            'name' => 'F',
            'city' => 'X',
            'district' => 'Y',
            'domain' => 'fc-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
        ]);
        $month = now()->format('Y-m');

        $response = $this->actingAs($admin)->get('/firma/finans/mutabakat?month='.$month.'&export=csv');

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('mutabakat-', (string) $response->headers->get('content-disposition'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('gun', $content);
        $this->assertStringContainsString('isletme_paket', $content);
        $this->assertStringContainsString('TOPLAM', $content);
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
