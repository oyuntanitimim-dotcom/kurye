<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RestaurantBusinessType;
use App\Models\User;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Couriers\Models\CourierLocation;
use App\Modules\Firms\Models\Firm;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourierLocationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_courier_location_requires_courier_profile(): void
    {
        $this->seedRoles();

        $firm = Firm::query()->create([
            'name' => 'F',
            'city' => 'c',
            'district' => 'd',
            'domain' => 'f-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::FIRM_ADMIN)->value('id'),
        ]);

        $token = $user->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/courier/location', [
                'latitude' => 38.42,
                'longitude' => 27.13,
            ])
            ->assertForbidden();
    }

    public function test_courier_can_post_location_upserts_row(): void
    {
        $this->seedRoles();

        $firm = Firm::query()->create([
            'name' => 'F',
            'city' => 'c',
            'district' => 'd',
            'domain' => 'f-'.Str::uuid()->toString().'.local',
            'platform_fee_per_order' => 0,
            'default_restaurant_fee_per_delivery' => 0,
            'status' => 'active',
        ]);

        Restaurant::query()->create([
            'firm_id' => $firm->id,
            'name' => 'R',
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
        ]);

        $courierUser = User::factory()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::COURIER)->value('id'),
        ]);

        $courier = Courier::query()->create([
            'firm_id' => $firm->id,
            'user_id' => $courierUser->id,
            'name' => 'K',
            'phone' => '05551112233',
            'vehicle_type' => 'motosiklet',
            'status' => 'active',
        ]);

        $token = $courierUser->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/courier/location', [
                'latitude' => 38.42,
                'longitude' => 27.13,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $loc = CourierLocation::query()->where('courier_id', $courier->id)->first();
        $this->assertNotNull($loc);
        $this->assertEqualsWithDelta(38.42, (float) $loc->latitude, 0.0001);
        $this->assertEqualsWithDelta(27.13, (float) $loc->longitude, 0.0001);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/courier/location', [
                'latitude' => 38.5,
                'longitude' => 27.2,
            ])
            ->assertOk();

        $this->assertSame(1, CourierLocation::query()->where('courier_id', $courier->id)->count());
        $loc->refresh();
        $this->assertEqualsWithDelta(38.5, (float) $loc->latitude, 0.0001);
        $this->assertEqualsWithDelta(27.2, (float) $loc->longitude, 0.0001);
    }

    private function seedRoles(): void
    {
        foreach ([Role::FIRM_ADMIN, Role::COURIER] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }
    }
}
