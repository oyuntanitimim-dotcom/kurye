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

class AdminFirmRestaurantBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_firm_show_and_filter_restaurants_by_firm(): void
    {
        Role::query()->firstOrCreate(['name' => Role::SUPER_ADMIN]);

        $firmA = Firm::query()->create([
            'name' => 'Kurye A',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'fa-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 1,
            'default_restaurant_fee_per_delivery' => 2,
            'status' => 'active',
        ]);

        $firmB = Firm::query()->create([
            'name' => 'Kurye B',
            'city' => 'Ankara',
            'district' => 'Çankaya',
            'domain' => 'fb-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 1,
            'default_restaurant_fee_per_delivery' => 2,
            'status' => 'active',
        ]);

        Restaurant::query()->create([
            'firm_id' => $firmA->id,
            'name' => 'Restoran Sadece A',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 1,
            'longitude' => 1,
        ]);

        Restaurant::query()->create([
            'firm_id' => $firmB->id,
            'name' => 'Restoran Sadece B',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'latitude' => 1,
            'longitude' => 1,
        ]);

        $super = User::factory()->create([
            'firm_id' => null,
            'role_id' => Role::query()->where('name', Role::SUPER_ADMIN)->value('id'),
        ]);

        $this->actingAs($super)->get(route('admin.firms.show', $firmA))
            ->assertOk()
            ->assertSee('Kurye A', false)
            ->assertSee('Restoranlar (1)', false);

        $this->actingAs($super)->get(route('admin.restaurants.index', ['firm_id' => $firmA->id]))
            ->assertOk()
            ->assertSee('Restoran Sadece A', false)
            ->assertDontSee('Restoran Sadece B', false);
    }
}
