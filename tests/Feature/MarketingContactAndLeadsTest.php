<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Marketing\MarketingContactLead;
use App\Models\User;
use App\Modules\Firms\Models\Firm;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingContactAndLeadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_creates_lead_and_redirects(): void
    {
        foreach ([Role::SUPER_ADMIN] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }
        Firm::query()->create([
            'name' => 'F',
            'city' => 'c',
            'district' => 'd',
            'domain' => 'localhost',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);

        $this->post('/iletisim', [
            'name' => 'Demo Kullanıcı',
            'email' => 'demo@example.test',
            'phone' => '05551112233',
            'company' => 'Demo A.Ş.',
            'message' => 'Kurulum hakkında bilgi istiyorum.',
        ])->assertRedirect();

        $this->assertDatabaseHas('marketing_contact_leads', [
            'email' => 'demo@example.test',
            'name' => 'Demo Kullanıcı',
        ]);
    }

    public function test_contact_validation_redirects_with_errors(): void
    {
        Firm::query()->create([
            'name' => 'F',
            'city' => 'c',
            'district' => 'd',
            'domain' => 'localhost',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);

        $this->from('/')
            ->post('/iletisim', [
                'name' => '',
                'email' => 'not-an-email',
                'message' => '',
            ])
            ->assertRedirect(route('marketing.home').'#iletisim')
            ->assertSessionHasErrors();
    }

    public function test_honeypot_field_returns_403(): void
    {
        Firm::query()->create([
            'name' => 'F',
            'city' => 'c',
            'district' => 'd',
            'domain' => 'localhost',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);

        $this->post('/iletisim', [
            'website' => 'http://spam.example',
            'name' => 'X',
            'email' => 'x@y.test',
            'message' => 'spam',
        ])->assertForbidden();
    }

    public function test_super_admin_can_list_and_open_lead(): void
    {
        foreach ([Role::SUPER_ADMIN] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }

        $lead = MarketingContactLead::query()->create([
            'name' => 'A',
            'email' => 'a@b.test',
            'phone' => null,
            'company' => null,
            'message' => 'Merhaba',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'read' => false,
        ]);

        $super = User::factory()->create([
            'firm_id' => null,
            'role_id' => Role::query()->where('name', Role::SUPER_ADMIN)->value('id'),
        ]);

        $this->actingAs($super)->get('/admin/marketing/basvurular')->assertOk()->assertSee('a@b.test', false);
        $this->actingAs($super)->get('/admin/marketing/basvurular/'.$lead->id)->assertOk()->assertSee('Merhaba', false);

        $this->assertTrue($lead->fresh()->read);
    }
}
