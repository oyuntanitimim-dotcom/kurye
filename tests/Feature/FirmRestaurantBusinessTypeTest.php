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

class FirmRestaurantBusinessTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_default_categories_from_market_template(): void
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
        $admin = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
        ]);

        $email = 'owner-'.Str::uuid()->toString().'@example.test';
        $response = $this->actingAs($admin)->post('/firma/restoranlar', [
            'business_type' => 'market',
            'name' => 'Büyük Market',
            'status' => 'active',
            'admin_name' => 'Patron',
            'admin_email' => $email,
            'admin_password' => 'password12',
        ]);

        $response->assertRedirect(route('firm.restaurants.index'));
        $restaurant = Restaurant::query()->where('name', 'Büyük Market')->firstOrFail();
        $this->assertSame(RestaurantBusinessType::Market, $restaurant->business_type);
        $names = $restaurant->categories()->orderBy('sort_order')->pluck('name')->all();
        $this->assertSame(config('restaurant_menu_templates.types.market'), $names);
    }

    public function test_store_kebab_template_differs_from_restaurant(): void
    {
        $this->seedRoles();
        $firm = Firm::query()->create([
            'name' => 'F2',
            'city' => 'X',
            'district' => 'Y',
            'domain' => 'f2-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
        ]);

        $this->actingAs($admin)->post('/firma/restoranlar', [
            'business_type' => 'kebab',
            'name' => 'Kebapçı Ali',
            'status' => 'active',
            'admin_name' => 'Ali',
            'admin_email' => 'ali-'.Str::uuid()->toString().'@example.test',
            'admin_password' => 'password12',
        ])->assertRedirect(route('firm.restaurants.index'));

        $restaurant = Restaurant::query()->where('name', 'Kebapçı Ali')->firstOrFail();
        $kebabNames = $restaurant->categories()->orderBy('sort_order')->pluck('name')->all();
        $this->assertSame(config('restaurant_menu_templates.types.kebab'), $kebabNames);
        $this->assertNotSame(config('restaurant_menu_templates.types.restaurant'), $kebabNames);
    }

    public function test_store_requires_business_type(): void
    {
        $this->seedRoles();
        $firm = Firm::query()->create([
            'name' => 'F3',
            'city' => 'X',
            'district' => 'Y',
            'domain' => 'f3-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
        ]);

        $response = $this->actingAs($admin)->post('/firma/restoranlar', [
            'name' => 'X',
            'status' => 'active',
            'admin_name' => 'A',
            'admin_email' => 'a-'.Str::uuid()->toString().'@example.test',
            'admin_password' => 'password12',
        ]);

        $response->assertSessionHasErrors('business_type');
    }

    public function test_update_can_change_business_type_and_rebuild_categories(): void
    {
        $this->seedRoles();
        $firm = Firm::query()->create([
            'name' => 'F4',
            'city' => 'X',
            'district' => 'Y',
            'domain' => 'f4-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
        ]);

        $this->actingAs($admin)->post('/firma/restoranlar', [
            'business_type' => 'market',
            'name' => 'Tip Değişecek Market',
            'latitude' => '38.41',
            'longitude' => '27.12',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
            'status' => 'active',
            'admin_name' => 'Yönetici',
            'admin_email' => 'tip-'.Str::uuid()->toString().'@example.test',
            'admin_password' => 'password12',
        ])->assertRedirect(route('firm.restaurants.index'));

        $restaurant = Restaurant::query()->where('name', 'Tip Değişecek Market')->firstOrFail();
        $this->assertSame(config('restaurant_menu_templates.types.market'), $restaurant->categories()->orderBy('sort_order')->pluck('name')->all());

        $manager = \App\Support\RestaurantPrimaryManager::user($restaurant);
        $this->assertNotNull($manager);

        $response = $this->actingAs($admin)->put(route('firm.restaurants.update', $restaurant), [
            'business_type' => 'pharmacy',
            'name' => $restaurant->name,
            'phone' => $restaurant->phone,
            'address' => $restaurant->address,
            'latitude' => (string) $restaurant->latitude,
            'longitude' => (string) $restaurant->longitude,
            'opening_time' => $restaurant->opening_time ? substr((string) $restaurant->opening_time, 0, 5) : '',
            'closing_time' => $restaurant->closing_time ? substr((string) $restaurant->closing_time, 0, 5) : '',
            'status' => $restaurant->status,
            'fee_per_delivery' => '',
            'manager_name' => $manager->name,
            'manager_email' => $manager->email,
        ]);

        $response->assertRedirect(route('firm.restaurants.index'))
            ->assertSessionHas('status');

        $restaurant->refresh();
        $this->assertSame(RestaurantBusinessType::Pharmacy, $restaurant->business_type);
        $this->assertSame(
            config('restaurant_menu_templates.types.pharmacy'),
            $restaurant->categories()->orderBy('sort_order')->pluck('name')->all()
        );
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
