<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\RestaurantBusinessType;
use App\Modules\Integrations\Models\IntegrationConnection;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Models\Campaign;
use App\Modules\Firms\Models\Coupon;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Models\OrderStatusHistory;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Restaurants\Models\RestaurantCategory;
use App\Modules\Restaurants\Services\RestaurantMenuTemplateService;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use App\Services\Marketing\MarketingBootstrap;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            Role::SUPER_ADMIN => 'Süper Yönetici',
            Role::FIRM_ADMIN => 'Kurye şirketi yöneticisi',
            Role::RESTAURANT => 'Firma',
            Role::COURIER => 'Kurye',
            Role::CUSTOMER => 'Müşteri',
        ];

        foreach ($roles as $slug => $_label) {
            Role::query()->firstOrCreate(['name' => $slug]);
        }

        $r = fn (string $name): Role => Role::query()->where('name', $name)->firstOrFail();

        $firmA = Firm::query()->create([
            'name' => 'Demo İlçe A.Ş.',
            'city' => 'İzmir',
            'district' => 'Konak',
            'domain' => 'localhost',
            'logo' => null,
            'platform_fee_per_order' => 2.5,
            'default_restaurant_fee_per_delivery' => 3.5,
            'opening_hours' => ['mon' => ['09:00', '23:00'], 'sun' => ['10:00', '22:00']],
            'status' => 'active',
        ]);

        $firmB = Firm::query()->create([
            'name' => 'Hızlı Teslimat Ltd.',
            'city' => 'Ankara',
            'district' => 'Çankaya',
            'domain' => '127.0.0.1',
            'logo' => null,
            'platform_fee_per_order' => 1.5,
            'default_restaurant_fee_per_delivery' => 2.0,
            'opening_hours' => null,
            'status' => 'active',
        ]);

        User::query()->create([
            'firm_id' => null,
            'role_id' => $r(Role::SUPER_ADMIN)->id,
            'name' => 'Süper Admin',
            'email' => 'admin@kurye.local',
            'phone' => null,
            'password' => 'password',
            'status' => 'active',
        ]);

        $adminA = User::query()->create([
            'firm_id' => $firmA->id,
            'role_id' => $r(Role::FIRM_ADMIN)->id,
            'name' => 'Kurye şirketi yöneticisi A',
            'email' => 'firma-a@demo.local',
            'phone' => '05551112233',
            'password' => 'password',
            'status' => 'active',
        ]);

        User::query()->create([
            'firm_id' => $firmB->id,
            'role_id' => $r(Role::FIRM_ADMIN)->id,
            'name' => 'Kurye şirketi yöneticisi B',
            'email' => 'firma-b@demo.local',
            'phone' => '05554443322',
            'password' => 'password',
            'status' => 'active',
        ]);

        $restA = Restaurant::query()->create([
            'firm_id' => $firmA->id,
            'name' => 'Konak Sofrası Lokantası',
            'logo' => null,
            'phone' => '0232 311 22 33',
            'address' => 'Mithatpaşa Cad. No:12, Konak / İzmir',
            'latitude' => 38.4189,
            'longitude' => 27.1287,
            'status' => 'active',
            'business_type' => RestaurantBusinessType::Restaurant,
            'opening_time' => '09:00:00',
            'closing_time' => '23:00:00',
        ]);

        $catPizza = RestaurantCategory::query()->create([
            'restaurant_id' => $restA->id,
            'name' => 'Pide & Pizza',
            'sort_order' => 1,
        ]);

        $catDrink = RestaurantCategory::query()->create([
            'restaurant_id' => $restA->id,
            'name' => 'İçecek',
            'sort_order' => 2,
        ]);

        $p1 = Product::query()->create([
            'restaurant_id' => $restA->id,
            'category_id' => $catPizza->id,
            'name' => 'Karışık Pide',
            'description' => 'Kıyma, kaşar, biber',
            'price' => 185.00,
            'image' => null,
            'status' => 'active',
            'stock' => 50,
        ]);

        $p2 = Product::query()->create([
            'restaurant_id' => $restA->id,
            'category_id' => $catPizza->id,
            'name' => 'Lahmacun',
            'description' => 'Acılı',
            'price' => 45.00,
            'image' => null,
            'status' => 'active',
            'stock' => 100,
        ]);

        Product::query()->create([
            'restaurant_id' => $restA->id,
            'category_id' => $catDrink->id,
            'name' => 'Ayran',
            'description' => null,
            'price' => 20.00,
            'image' => null,
            'status' => 'active',
            'stock' => 200,
        ]);

        $restUser = User::query()->create([
            'firm_id' => $firmA->id,
            'role_id' => $r(Role::RESTAURANT)->id,
            'restaurant_id' => $restA->id,
            'name' => 'Firma yetkilisi',
            'email' => 'restoran@demo.local',
            'phone' => '05556667788',
            'password' => 'password',
            'status' => 'active',
        ]);

        $courierUser = User::query()->create([
            'firm_id' => $firmA->id,
            'role_id' => $r(Role::COURIER)->id,
            'name' => 'Kurye Ali',
            'email' => 'kurye@demo.local',
            'phone' => '05559998877',
            'password' => 'password',
            'status' => 'active',
        ]);

        Courier::query()->create([
            'firm_id' => $firmA->id,
            'user_id' => $courierUser->id,
            'name' => 'Kurye Ali',
            'phone' => '05559998877',
            'vehicle_type' => 'motosiklet',
            'status' => 'active',
            'compensation_type' => 'per_delivery',
            'compensation_per_delivery' => 35.00,
            'compensation_monthly_salary' => null,
            'compensation_per_km' => null,
            'compensation_notes' => null,
        ]);

        $customer = User::query()->create([
            'firm_id' => $firmA->id,
            'role_id' => $r(Role::CUSTOMER)->id,
            'name' => 'Müşteri Ayşe',
            'email' => 'musteri@demo.local',
            'phone' => '05551234567',
            'password' => 'password',
            'status' => 'active',
        ]);

        $customer->addresses()->create([
            'title' => 'Ev',
            'address' => 'Alsancak Mah. No:1 Konak/İzmir',
            'latitude' => 38.4375,
            'longitude' => 27.1428,
        ]);

        Campaign::query()->create([
            'firm_id' => $firmA->id,
            'name' => 'Hafta Sonu %10',
            'discount_rate' => 10,
            'min_order' => 100,
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
        ]);

        Coupon::query()->create([
            'firm_id' => $firmA->id,
            'code' => 'HOSGELDIN20',
            'discount' => 20,
            'usage_limit' => 100,
            'used_count' => 0,
            'expire_date' => now()->addMonths(3)->toDateString(),
        ]);

        $addr = $customer->addresses()->first();

        $order = Order::query()->create([
            'firm_id' => $firmA->id,
            'user_id' => $customer->id,
            'restaurant_id' => $restA->id,
            'courier_id' => null,
            'delivery_address_id' => $addr?->id,
            'status' => OrderStatus::Pending->value,
            'total_price' => 230.00,
            'delivery_fee' => 15.00,
            'discount_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'campaign_id' => null,
            'coupon_id' => null,
            'notes' => null,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $p1->id,
            'price' => $p1->price,
            'quantity' => 1,
            'product_name' => $p1->name,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $p2->id,
            'price' => $p2->price,
            'quantity' => 1,
            'product_name' => $p2->name,
        ]);

        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'status' => OrderStatus::Pending->value,
            'meta' => null,
            'created_at' => now(),
        ]);

        IntegrationConnection::query()->create([
            'firm_id' => $firmA->id,
            'restaurant_id' => $restA->id,
            'provider' => 'pilot',
            'credentials_encrypted' => null,
            'settings_json' => ['webhook_token' => 'dev-integration-token'],
            'is_active' => true,
        ]);

        $menuTemplate = app(RestaurantMenuTemplateService::class);

        $demoShops = [
            [RestaurantBusinessType::FastFood, 'Lezzet Tost & Burger — Alsancak', '0232 440 00 01', 'Kültür Mah. 1456 Sok. No:4, Konak'],
            [RestaurantBusinessType::Kebab, 'Bostanlı Öz Urfa Kebap', '0232 440 00 02', 'Bostanlı Çarşı içi, Karşıyaka'],
            [RestaurantBusinessType::Cafe, 'Kordon Espresso Lab', '0232 440 00 03', 'Atatürk Cad., Alsancak'],
            [RestaurantBusinessType::Bakery, 'Fırın Saraylı — Günlük Ekmek', '0232 440 00 04', 'Gazi Osman Paşa Bulvarı, Bornova'],
            [RestaurantBusinessType::Market, 'Tam Teşekküllü Mini Market Kültür', '0232 440 00 05', 'Kültür Mah., Konak'],
            [RestaurantBusinessType::Grocery, 'Mahalle Bakkalı Necati Usta', '0232 440 00 06', 'Yenişehir Mah., Konak'],
            [RestaurantBusinessType::Greengrocer, 'Taze Köşe Manav', '0232 440 00 07', 'Hatay Mah., Konak'],
            [RestaurantBusinessType::Pharmacy, 'Sağlık Eczanesi — Alsancak', '0232 440 00 08', 'Alsancak, 356 Konak'],
            [RestaurantBusinessType::Florist, 'Gülhane Çiçekçilik', '0232 440 00 09', 'Alsancak, Konak'],
            [RestaurantBusinessType::Pet, 'Patili Dünya Pet Shop', '0232 440 00 10', 'Ergene Sok., Bornova'],
            [RestaurantBusinessType::NutsConfectionery, 'Antep Kuruyemiş & Lokum Evi', '0232 440 00 11', 'Kemeraltı, Konak'],
            [RestaurantBusinessType::Gift, 'Hediyeni Seç Konsept Mağaza', '0232 440 00 12', 'Forum Bornova civarı, Bornova'],
            [RestaurantBusinessType::BookstoreStationery, 'Dersane Sokak Kitap & Kırtasiye', '0232 440 00 13', 'Bornova merkez'],
            [RestaurantBusinessType::WaterBeverageDispatch, 'Damla Su Dağıtım — Günlük Teslimat', '0232 440 00 14', 'Güzelyalı, Konak'],
        ];

        $latBase = 38.422;
        $lngBase = 27.131;
        $i = 0;
        foreach ($demoShops as [$businessType, $shopName, $phone, $address]) {
            $i++;
            $rest = Restaurant::query()->create([
                'firm_id' => $firmA->id,
                'name' => $shopName,
                'logo' => null,
                'phone' => $phone,
                'address' => $address,
                'latitude' => $latBase + ($i * 0.004),
                'longitude' => $lngBase + ($i * 0.003),
                'status' => 'active',
                'business_type' => $businessType,
                'opening_time' => '08:00:00',
                'closing_time' => '22:00:00',
            ]);
            $menuTemplate->apply($rest, $businessType);

            User::query()->create([
                'firm_id' => $firmA->id,
                'role_id' => $r(Role::RESTAURANT)->id,
                'restaurant_id' => $rest->id,
                'name' => $shopName.' yetkilisi',
                'email' => 'firma-'.$businessType->value.'@demo.local',
                'phone' => '0555 440 '.str_pad((string) (10 + $i), 4, '0', STR_PAD_LEFT),
                'password' => 'password',
                'status' => 'active',
            ]);
        }

        $restBFast = Restaurant::query()->create([
            'firm_id' => $firmB->id,
            'name' => 'Kızılay Lezzet Durağı — Tost & İçecek',
            'logo' => null,
            'phone' => '0312 550 00 01',
            'address' => 'Kızılay, Çankaya / Ankara',
            'latitude' => 39.9208,
            'longitude' => 32.8541,
            'status' => 'active',
            'business_type' => RestaurantBusinessType::FastFood,
            'opening_time' => '07:00:00',
            'closing_time' => '23:30:00',
        ]);
        $menuTemplate->apply($restBFast, RestaurantBusinessType::FastFood);
        User::query()->create([
            'firm_id' => $firmB->id,
            'role_id' => $r(Role::RESTAURANT)->id,
            'restaurant_id' => $restBFast->id,
            'name' => 'Kızılay Lezzet Durağı yetkilisi',
            'email' => 'firma-b-ornek@demo.local',
            'phone' => '0555 550 2001',
            'password' => 'password',
            'status' => 'active',
        ]);

        unset($adminA, $restUser);

        if (! app()->environment('testing')) {
            app(MarketingBootstrap::class)->ensureSiteExists();
        }
    }
}
