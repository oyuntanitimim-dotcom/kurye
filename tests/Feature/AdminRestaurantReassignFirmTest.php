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

class AdminRestaurantReassignFirmTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_reassign_restaurant_updates_restaurant_admin_firm_id(): void
    {
        Role::query()->firstOrCreate(['name' => Role::SUPER_ADMIN]);
        Role::query()->firstOrCreate(['name' => Role::RESTAURANT]);

        $firmA = Firm::query()->create([
            'name' => 'Şirket A',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'fa-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);

        $firmB = Firm::query()->create([
            'name' => 'Şirket B',
            'city' => 'Ankara',
            'district' => 'Çankaya',
            'domain' => 'fb-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);

        $restaurant = Restaurant::query()->create([
            'firm_id' => $firmA->id,
            'name' => 'Taşınacak Restoran',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 1,
            'longitude' => 1,
            'phone' => '02120001122',
        ]);

        $restaurantUser = User::query()->create([
            'firm_id' => $firmA->id,
            'restaurant_id' => $restaurant->id,
            'role_id' => Role::query()->where('name', Role::RESTAURANT)->value('id'),
            'name' => 'Restoran Yetkilisi',
            'email' => 'ry-'.Str::uuid()->toString().'@example.test',
            'password' => bcrypt('secret-12345'),
            'status' => 'active',
        ]);

        $super = User::factory()->create([
            'firm_id' => null,
            'role_id' => Role::query()->where('name', Role::SUPER_ADMIN)->value('id'),
        ]);

        $payload = [
            'admin_firm_id' => $firmB->id,
            'business_type' => $restaurant->business_type->value,
            'name' => $restaurant->name,
            'phone' => $restaurant->phone,
            'address' => $restaurant->address,
            'latitude' => (string) $restaurant->latitude,
            'longitude' => (string) $restaurant->longitude,
            'opening_time' => '',
            'closing_time' => '',
            'status' => 'active',
            'fee_per_delivery' => '',
            'shop_delivery_fee' => '',
            'manager_name' => $restaurantUser->name,
            'manager_email' => $restaurantUser->email,
            'manager_password' => '',
            'manager_password_confirmation' => '',
        ];

        $this->actingAs($super)
            ->put(route('admin.restaurants.update', $restaurant), $payload)
            ->assertRedirect(route('admin.restaurants.index'));

        $this->assertSame($firmB->id, $restaurant->fresh()->firm_id);
        $this->assertSame($firmB->id, (int) $restaurantUser->fresh()->firm_id);
    }
}
