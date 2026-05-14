<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RestaurantBusinessType;
use App\Models\User;
use App\Modules\Firms\Models\Firm;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Address;
use App\Modules\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GeocodeMissingAddressesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_updates_missing_coords(): void
    {
        foreach ([Role::CUSTOMER] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }

        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                ['lat' => '38.1111111', 'lon' => '27.2222222'],
            ], 200),
        ]);

        $firm = Firm::query()->create([
            'name' => 'G',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'localhost',
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

        $customer = User::query()->create([
            'firm_id' => $firm->id,
            'role_id' => Role::query()->where('name', Role::CUSTOMER)->value('id'),
            'name' => 'Alıcı',
            'email' => 'geo-'.Str::uuid()->toString().'@example.test',
            'password' => 'password12',
            'status' => 'active',
        ]);

        $a = Address::query()->create([
            'user_id' => $customer->id,
            'title' => 'Ev',
            'address' => 'Konak, İzmir',
            'latitude' => null,
            'longitude' => null,
        ]);

        $code = Artisan::call('kurye:geocode-missing-addresses', [
            '--limit' => 10,
            '--sleep_ms' => 0,
        ]);
        $this->assertSame(0, $code);

        $a->refresh();
        $this->assertEqualsWithDelta(38.1111111, (float) $a->latitude, 0.0001);
        $this->assertEqualsWithDelta(27.2222222, (float) $a->longitude, 0.0001);
    }
}

