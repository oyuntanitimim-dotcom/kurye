<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CourierCompensationType;
use App\Enums\OrderStatus;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedScenarioDataCommand extends Command
{
    protected $signature = 'kurye:seed-scenarios
        {--firm_domain=localhost : Firma domain (default: localhost)}
        {--couriers=30 : Oluşturulacak kurye sayısı}
        {--orders=500 : Oluşturulacak sipariş sayısı}
        {--customers=120 : Oluşturulacak müşteri sayısı}
        {--dry-run : Veritabanına yazmadan simülasyon}';

    protected $description = 'Demo senaryo verileri: kuryeler ve çeşitli statülerde siparişler üretir.';

    public function handle(): int
    {
        $firmDomain = (string) $this->option('firm_domain');
        $courierCount = max(0, (int) $this->option('couriers'));
        $orderCount = max(0, (int) $this->option('orders'));
        $customerCount = max(10, (int) $this->option('customers'));
        $dryRun = (bool) $this->option('dry-run');

        $firm = Firm::query()->where('domain', $firmDomain)->first()
            ?? Firm::query()->orderBy('id')->first();

        if (! $firm) {
            $this->error('Firma bulunamadı. Önce `php artisan db:seed` çalıştırın.');
            return self::FAILURE;
        }

        $restaurants = Restaurant::query()
            ->where('firm_id', $firm->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->get(['id', 'name', 'fee_per_delivery']);

        if ($restaurants->isEmpty()) {
            $this->error('Firma altında işletme yok. Önce `php artisan db:seed` çalıştırın.');
            return self::FAILURE;
        }

        $courierRoleId = Role::query()->where('name', Role::COURIER)->value('id');
        $customerRoleId = Role::query()->where('name', Role::CUSTOMER)->value('id');
        if (! $courierRoleId || ! $customerRoleId) {
            $this->error('Role kayıtları eksik. Önce `php artisan db:seed` çalıştırın.');
            return self::FAILURE;
        }

        $this->info("Firma: #{$firm->id} {$firm->name} ({$firm->domain})");
        $this->info('İşletme sayısı: '.$restaurants->count());

        $statuses = $this->statusWeights();

        if ($dryRun) {
            $this->warn('DRY RUN: veritabanına yazılmayacak.');
        }

        $createdCouriers = 0;
        $createdCustomers = 0;
        $createdOrders = 0;

        $start = microtime(true);

        DB::beginTransaction();
        try {
            // 1) Couriers (+ users)
            $this->line("Kurye üretiliyor: {$courierCount}");
            $couriers = collect();
            $restForGeo = $restaurants->first();
            $baseLat = $restForGeo?->latitude !== null ? (float) $restForGeo->latitude : 38.422;
            $baseLng = $restForGeo?->longitude !== null ? (float) $restForGeo->longitude : 27.131;
            for ($i = 1; $i <= $courierCount; $i++) {
                $suffix = Str::lower(Str::random(6));
                $email = "demo-kurye-{$firm->id}-{$suffix}@seed.local";

                $user = User::query()->create([
                    'firm_id' => $firm->id,
                    'role_id' => $courierRoleId,
                    'restaurant_id' => null,
                    'name' => "Demo Kurye {$i}",
                    'email' => $email,
                    'phone' => $this->fakePhone($i),
                    'password' => 'password',
                    'status' => 'active',
                ]);

                $courier = Courier::query()->create([
                    'firm_id' => $firm->id,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'phone' => (string) $user->phone,
                    'vehicle_type' => collect(['motosiklet', 'bisiklet', 'araba'])->random(),
                    'status' => 'active',
                    'compensation_type' => CourierCompensationType::PerDelivery->value,
                    'compensation_per_delivery' => 50.0,
                    'compensation_monthly_salary' => null,
                    'compensation_per_km' => null,
                    'compensation_notes' => null,
                ]);

                // Havuz/otomatik atama için konum gerekli (aksi halde no_location ile elenir)
                DB::table('courier_locations')->insert([
                    'courier_id' => $courier->id,
                    'latitude' => $baseLat + (random_int(-120, 120) / 10000),
                    'longitude' => $baseLng + (random_int(-120, 120) / 10000),
                    'updated_at' => now(),
                ]);

                $couriers->push($courier);
                $createdCouriers++;
            }

            // 2) Customers (+ address)
            $this->line("Müşteri üretiliyor: {$customerCount}");
            $customers = collect();
            for ($i = 1; $i <= $customerCount; $i++) {
                $suffix = Str::lower(Str::random(6));
                $email = "demo-musteri-{$firm->id}-{$suffix}@seed.local";

                $user = User::query()->create([
                    'firm_id' => $firm->id,
                    'role_id' => $customerRoleId,
                    'restaurant_id' => null,
                    'name' => "Demo Müşteri {$i}",
                    'email' => $email,
                    'phone' => $this->fakePhone(1000 + $i),
                    'password' => 'password',
                    'status' => 'active',
                ]);

                $user->addresses()->create([
                    'title' => 'Ev',
                    'address' => 'Demo adres '.$i,
                    'latitude' => 38.42 + ($i * 0.0005),
                    'longitude' => 27.13 + ($i * 0.0004),
                ]);

                $customers->push($user->fresh(['addresses']));
                $createdCustomers++;
            }

            // 3) Orders (+ items optional)
            $this->line("Sipariş üretiliyor: {$orderCount}");
            $bar = $this->output->createProgressBar($orderCount);
            $bar->start();

            $productIdsByRestaurant = Product::query()
                ->whereIn('restaurant_id', $restaurants->pluck('id'))
                ->pluck('id', 'restaurant_id')
                ->groupBy(fn ($productId, $restaurantId) => $restaurantId);

            $platformFeePerOrder = (float) ($firm->platform_fee_per_order ?? 0);
            $defaultRestaurantFee = (float) ($firm->default_restaurant_fee_per_delivery ?? 0);
            $defaultDeliveryFee = (float) ($firm->mergedOperationSettings()['default_delivery_fee'] ?? 0);

            for ($i = 1; $i <= $orderCount; $i++) {
                $status = $this->pickWeighted($statuses);

                $restaurant = $restaurants->random();
                $customer = $customers->random();
                $addr = $customer->addresses->first();

                $createdAt = now()->subDays(random_int(0, 29))->subMinutes(random_int(0, 1440));
                $updatedAt = (clone $createdAt)->addMinutes(random_int(5, 240));
                if ($status === OrderStatus::Delivered->value) {
                    $updatedAt = (clone $createdAt)->addMinutes(random_int(30, 180));
                }
                if ($status === OrderStatus::Cancelled->value) {
                    $updatedAt = (clone $createdAt)->addMinutes(random_int(2, 60));
                }

                $courier = null;
                $courierPayout = null;
                if (in_array($status, [
                    OrderStatus::CourierAssigned->value,
                    OrderStatus::PickedUp->value,
                    OrderStatus::OnTheWay->value,
                    OrderStatus::Delivered->value,
                ], true)) {
                    $courier = $couriers->random();
                    $courierPayout = (float) ($courier->compensation_per_delivery ?? 0);
                }

                // Demo veride raporu şaşırtmamak için rastgele indirim/teslimat yok.
                // Teslimat: işletme bazlı shop_delivery_fee varsa onu, yoksa firma varsayılanını kullan.
                $deliveryFee = $restaurant->shop_delivery_fee !== null
                    ? (float) $restaurant->shop_delivery_fee
                    : $defaultDeliveryFee;
                $discount = 0.0;
                $subTotal = (float) random_int(120, 600);
                $total = max(0, $subTotal + $deliveryFee - $discount);

                $restaurantCommission = (float) ($restaurant->fee_per_delivery ?? $defaultRestaurantFee ?? 0);

                $order = new Order([
                    'firm_id' => $firm->id,
                    'user_id' => $customer->id,
                    'restaurant_id' => $restaurant->id,
                    'courier_id' => $courier?->id,
                    'delivery_address_id' => $addr?->id,
                    'status' => $status,
                    'total_price' => $total,
                    'delivery_fee' => $deliveryFee,
                    'discount_amount' => $discount,
                    'platform_fee_amount' => $platformFeePerOrder,
                    'restaurant_commission_amount' => $restaurantCommission,
                    'courier_payout_amount' => $courierPayout,
                    'payment_method' => 'online',
                    'campaign_id' => null,
                    'coupon_id' => null,
                    'notes' => null,
                ]);

                $order->created_at = $createdAt;
                $order->updated_at = $updatedAt;
                $order->save();

                // Basit item (varsa ürünlerden)
                $pids = $productIdsByRestaurant[(string) $restaurant->id] ?? null;
                if ($pids && $pids->isNotEmpty()) {
                    $productId = (int) $pids->random();
                    DB::table('order_items')->insert([
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'price' => $total,
                        'quantity' => 1,
                        'product_name' => null,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                    ]);
                }

                $createdOrders++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $elapsed = number_format(microtime(true) - $start, 2);
        $this->info("Tamam. Kurye: {$createdCouriers}, Müşteri: {$createdCustomers}, Sipariş: {$createdOrders} (süre: {$elapsed}s)");
        $this->line('Not: Yeni demo kullanıcı şifreleri "password".');

        return self::SUCCESS;
    }

    /** @return array<string,int> */
    private function statusWeights(): array
    {
        return [
            OrderStatus::Pending->value => 35,
            OrderStatus::Accepted->value => 20,
            OrderStatus::Preparing->value => 25,
            OrderStatus::Ready->value => 35,
            OrderStatus::CourierAssigned->value => 30,
            OrderStatus::PickedUp->value => 25,
            OrderStatus::OnTheWay->value => 35,
            OrderStatus::Delivered->value => 180,
            OrderStatus::Cancelled->value => 15,
        ];
    }

    /** @param array<string,int> $weights */
    private function pickWeighted(array $weights): string
    {
        $total = array_sum($weights);
        $r = random_int(1, max(1, $total));
        foreach ($weights as $k => $w) {
            $r -= $w;
            if ($r <= 0) {
                return (string) $k;
            }
        }
        return array_key_first($weights) ?: OrderStatus::Pending->value;
    }

    private function fakePhone(int $seed): string
    {
        $n = 1000000 + ($seed % 8999999);
        return '05'.(string) (50 + ($seed % 10)).(string) $n;
    }
}

