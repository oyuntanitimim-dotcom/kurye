<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Firms\Models\Firm;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketingSiteAndShopRoutesTest extends TestCase
{
    use RefreshDatabase;

    private function seedFirmForShop(): Firm
    {
        foreach ([Role::SUPER_ADMIN, Role::CUSTOMER] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }

        return Firm::query()->create([
            'name' => 'Test Firm',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'localhost',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);
    }

    public function test_root_is_marketing_not_shop(): void
    {
        $this->seedFirmForShop();

        $this->get('/')
            ->assertOk()
            ->assertSee(config('app.name'), false)
            ->assertSeeText('Kurye Yönetimini Tek Panelden Kontrol Edin', false)
            ->assertSeeText('Güçlü Yönetim Paneli', false);
    }

    public function test_shop_home_is_under_alisveris(): void
    {
        $this->seedFirmForShop();

        $this->get('/alisveris')->assertOk();
    }

    public function test_legacy_restoranlar_redirects_to_alisveris(): void
    {
        $this->seedFirmForShop();

        $this->get('/restoranlar')->assertRedirect('/alisveris/restoranlar');
    }

    public function test_legacy_magaza_redirects_under_alisveris(): void
    {
        $this->seedFirmForShop();

        $this->get('/magaza/sepet')->assertRedirect('/alisveris/sepet');
    }

    public function test_admin_marketing_requires_super_admin(): void
    {
        foreach ([Role::SUPER_ADMIN, Role::FIRM_ADMIN] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }

        $firm = Firm::query()->create([
            'name' => 'F',
            'city' => 'c',
            'district' => 'd',
            'domain' => 'fa-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);

        $firmAdmin = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
        ]);

        $this->actingAs($firmAdmin)->get('/admin/marketing')->assertRedirect(route('login'));
    }

    public function test_super_admin_can_open_marketing_cms(): void
    {
        foreach ([Role::SUPER_ADMIN] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }

        $super = User::factory()->create([
            'firm_id' => null,
            'role_id' => Role::query()->where('name', Role::SUPER_ADMIN)->value('id'),
        ]);

        $this->actingAs($super)->get('/admin/marketing')->assertOk();
    }
}
