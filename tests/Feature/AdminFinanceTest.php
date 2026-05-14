<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Firms\Models\Firm;
use App\Modules\Users\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_finance_requires_super_admin(): void
    {
        foreach ([
            Role::SUPER_ADMIN,
            Role::FIRM_ADMIN,
        ] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }

        $firm = Firm::query()->create([
            'name' => 'X',
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

        $this->actingAs($firmAdmin)->get('/admin/finans')->assertRedirect();
    }

    public function test_admin_finance_ok_for_super_admin(): void
    {
        foreach ([Role::SUPER_ADMIN] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }

        $admin = User::factory()->create([
            'firm_id' => null,
            'role_id' => Role::query()->where('name', Role::SUPER_ADMIN)->value('id'),
        ]);

        $this->actingAs($admin)->get('/admin/finans')
            ->assertOk()
            ->assertSee('Genel durum', false)
            ->assertSee('Şirket ciroları', false);

        $this->actingAs($admin)->get('/admin/finans/kurye-sirketleri')
            ->assertOk()
            ->assertSee('Finans — Şirket ciroları', false);

        $month = now()->format('Y-m');
        $csv = $this->actingAs($admin)->get('/admin/finans/mutabakat?month='.$month.'&export=csv');
        $csv->assertOk();
        $this->assertStringContainsString('isletme_paket', $csv->streamedContent());
        $this->assertStringContainsString('TOPLAM', $csv->streamedContent());
    }
}
