<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Firms\Models\Firm;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiMobileAuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_courier_can_login_via_mobile_api(): void
    {
        $this->seedRoles();
        $user = $this->makeUser(Role::COURIER);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'role']]);

        $this->assertSame(Role::COURIER, $response->json('user.role'));
    }

    public function test_customer_cannot_login_via_mobile_api(): void
    {
        $this->seedRoles();
        $user = $this->makeUser(Role::CUSTOMER);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_super_admin_cannot_login_via_mobile_api(): void
    {
        $this->seedRoles();
        $user = $this->makeUser(Role::SUPER_ADMIN);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_inactive_user_cannot_login_via_mobile_api(): void
    {
        $this->seedRoles();
        $user = $this->makeUser(Role::COURIER, 'inactive');

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_issues_token_with_expiration(): void
    {
        $this->seedRoles();
        $user = $this->makeUser(Role::FIRM_ADMIN);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk();
        $plain = (string) $response->json('token');
        $id = (int) str_contains($plain, '|') ? explode('|', $plain, 2)[0] : 0;
        $this->assertGreaterThan(0, $id);

        $token = PersonalAccessToken::query()->find($id);
        $this->assertNotNull($token);
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->isFuture());
    }

    private function makeUser(string $roleName, string $status = 'active'): User
    {
        $firm = Firm::query()->create([
            'name' => 'F',
            'city' => 'c',
            'district' => 'd',
            'domain' => 'f-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);

        return User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', $roleName)->value('id'),
            'status' => $status,
        ]);
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
