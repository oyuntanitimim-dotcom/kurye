<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RestaurantBusinessType;
use App\Models\User;
use App\Modules\Firms\Models\Firm;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminRestaurantEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_restaurant_edit_for_password_reset_flow(): void
    {
        Role::query()->firstOrCreate(['name' => Role::SUPER_ADMIN]);
        Role::query()->firstOrCreate(['name' => Role::RESTAURANT]);

        $firm = Firm::query()->create([
            'name' => 'X',
            'city' => 'c',
            'district' => 'd',
            'domain' => 'fa-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);

        $restaurant = Restaurant::query()->create([
            'firm_id' => $firm->id,
            'name' => 'Örnek İşletme',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Grocery,
            'latitude' => 1,
            'longitude' => 1,
        ]);

        User::query()->create([
            'firm_id' => $firm->id,
            'restaurant_id' => $restaurant->id,
            'role_id' => Role::query()->where('name', Role::RESTAURANT)->value('id'),
            'name' => 'Yetkili',
            'email' => 'isletme-'.Str::uuid()->toString().'@example.test',
            'password' => 'old-password-12',
            'status' => 'active',
        ]);

        $super = User::factory()->create([
            'firm_id' => null,
            'role_id' => Role::query()->where('name', Role::SUPER_ADMIN)->value('id'),
        ]);

        $this->actingAs($super)->get(route('admin.restaurants.edit', $restaurant))
            ->assertOk()
            ->assertSee('Restoran paneli girişi', false)
            ->assertSee('Giriş e-postası', false);
    }
}
