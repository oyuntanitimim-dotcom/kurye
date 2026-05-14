<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderSettlementService;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnsureCourierDeliveredOrdersCommand extends Command
{
    protected $signature = 'kurye:ensure-courier-delivered
        {--all-firms : Veritabanındaki tüm kurye şirketleri (firmalar)}
        {--firm_domain=localhost : Tek firma (all-firms kapalıyken)}
        {--min=30 : Kurye başına hedef minimum tamamlanmış sipariş}
        {--max=40 : Kurye başına hedef maksimum tamamlanmış sipariş}
        {--dry-run : Veritabanına yazma}';

    protected $description = 'Her firma için her aktif kuryeye 30–40 (veya min–max) arası teslim edilmiş sipariş ekler; mevcut teslimler hedefe göre tamamlanır.';

    public function handle(OrderSettlementService $settlementService): int
    {
        $allFirms = (bool) $this->option('all-firms');
        $firmDomain = (string) $this->option('firm_domain');
        $min = max(0, (int) $this->option('min'));
        $max = max($min, (int) $this->option('max'));
        $dryRun = (bool) $this->option('dry-run');

        if ($min < 1) {
            $this->error('min en az 1 olmalı.');

            return self::FAILURE;
        }

        $firms = $allFirms
            ? Firm::query()->orderBy('id')->get()
            : collect([Firm::query()->where('domain', $firmDomain)->first()
                ?? Firm::query()->orderBy('id')->first()])->filter();

        if ($firms->isEmpty()) {
            $this->error('Firma bulunamadı.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('DRY RUN: yazılmayacak.');
        }

        $totalCreated = 0;

        foreach ($firms as $firm) {
            if (! $firm) {
                continue;
            }
            $created = $this->processFirm($firm, $min, $max, $dryRun, $settlementService);
            $totalCreated += $created;
        }

        $this->info("Tamam. Eklenen teslim edilmiş sipariş: {$totalCreated}");

        return self::SUCCESS;
    }

    private function processFirm(Firm $firm, int $min, int $max, bool $dryRun, OrderSettlementService $settlementService): int
    {
        $restaurants = Restaurant::query()
            ->where('firm_id', $firm->id)
            ->where('status', 'active')
            ->get();

        if ($restaurants->isEmpty()) {
            $this->warn("Firma #{$firm->id}: aktif işletme yok, atlanıyor.");

            return 0;
        }

        $couriers = Courier::query()
            ->where('firm_id', $firm->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        if ($couriers->isEmpty()) {
            $this->warn("Firma #{$firm->id}: aktif kurye yok, atlanıyor.");

            return 0;
        }

        $customers = User::query()
            ->where('firm_id', $firm->id)
            ->where('role_id', Role::query()->where('name', Role::CUSTOMER)->value('id'))
            ->where('status', 'active')
            ->with('addresses')
            ->orderBy('id')
            ->limit(500)
            ->get()
            ->filter(fn (User $u) => $u->addresses->isNotEmpty());

        if ($customers->isEmpty()) {
            $this->warn("Firma #{$firm->id}: adresli müşteri yok; demo müşteri oluşturuluyor.");
            if (! $dryRun) {
                $this->createFallbackCustomer($firm->id);
                $customers = User::query()
                    ->where('firm_id', $firm->id)
                    ->where('role_id', Role::query()->where('name', Role::CUSTOMER)->value('id'))
                    ->with('addresses')
                    ->orderByDesc('id')
                    ->limit(20)
                    ->get()
                    ->filter(fn (User $u) => $u->addresses->isNotEmpty());
            } else {
                $this->line('(dry-run: müşteri yok sayıldı, firma atlandı.)');

                return 0;
            }
        }

        $productIdsByRestaurant = Product::query()
            ->whereIn('restaurant_id', $restaurants->pluck('id'))
            ->pluck('id', 'restaurant_id')
            ->groupBy(fn ($productId, $restaurantId) => $restaurantId);

        $defaultDeliveryFee = (float) ($firm->mergedOperationSettings()['default_delivery_fee'] ?? 0);

        $this->line("Firma: #{$firm->id} {$firm->name} — kurye: {$couriers->count()}, hedef aralık: {$min}–{$max} teslim/kurye");

        $created = 0;
        $delivered = OrderStatus::Delivered->value;

        DB::beginTransaction();
        try {
            foreach ($couriers as $courier) {
                $current = (int) Order::query()
                    ->where('firm_id', $firm->id)
                    ->where('courier_id', $courier->id)
                    ->where('status', $delivered)
                    ->count();

                $target = random_int($min, $max);
                $need = max(0, $target - $current);

                if ($need === 0) {
                    continue;
                }

                for ($n = 0; $n < $need; $n++) {
                    $restaurant = $restaurants->random();
                    $customer = $customers->random();
                    $addr = $customer->addresses->first();

                    $createdAt = now()->subDays(random_int(0, 45))->subMinutes(random_int(0, 1200));
                    $updatedAt = (clone $createdAt)->addMinutes(random_int(25, 120));

                    $deliveryFee = $restaurant->shop_delivery_fee !== null
                        ? (float) $restaurant->shop_delivery_fee
                        : $defaultDeliveryFee;
                    $discount = 0.0;
                    $subTotal = (float) random_int(100, 480);
                    $total = max(0.0, round($subTotal + $deliveryFee - $discount, 2));

                    $order = new Order([
                        'firm_id' => $firm->id,
                        'user_id' => $customer->id,
                        'restaurant_id' => $restaurant->id,
                        'courier_id' => $courier->id,
                        'delivery_address_id' => $addr?->id,
                        'status' => $delivered,
                        'total_price' => $total,
                        'delivery_fee' => $deliveryFee,
                        'discount_amount' => $discount,
                        'payment_method' => 'online',
                        'campaign_id' => null,
                        'coupon_id' => null,
                        'notes' => null,
                    ]);
                    $order->created_at = $createdAt;
                    $order->updated_at = $updatedAt;

                    if (! $dryRun) {
                        $order->loadMissing('firm', 'restaurant', 'courier');
                        $snap = $settlementService->snapshotForDelivery($order);
                        $order->platform_fee_amount = $snap['platform_fee_amount'];
                        $order->restaurant_commission_amount = $snap['restaurant_commission_amount'];
                        $order->courier_payout_amount = $snap['courier_payout_amount'];
                        $order->save();

                        $pids = $productIdsByRestaurant[(string) $restaurant->id] ?? null;
                        if ($pids && $pids->isNotEmpty()) {
                            $productId = (int) $pids->random();
                            DB::table('order_items')->insert([
                                'order_id' => $order->id,
                                'product_id' => $productId,
                                'price' => number_format($subTotal, 2, '.', ''),
                                'quantity' => 1,
                                'product_name' => null,
                                'created_at' => $createdAt,
                                'updated_at' => $updatedAt,
                            ]);
                        }
                    }

                    $created++;
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());

            return 0;
        }

        $this->info("  → Eklenen: {$created}");

        return $created;
    }

    private function createFallbackCustomer(int $firmId): void
    {
        $roleId = Role::query()->where('name', Role::CUSTOMER)->value('id');
        if (! $roleId) {
            return;
        }
        for ($i = 1; $i <= 15; $i++) {
            $suffix = Str::lower(Str::random(5));
            $u = User::query()->create([
                'firm_id' => $firmId,
                'role_id' => $roleId,
                'restaurant_id' => null,
                'name' => "Senaryo Müşteri {$i}",
                'email' => "senaryo-m-{$firmId}-{$i}-{$suffix}@seed.local",
                'phone' => '0555'.str_pad((string) (1000000 + $i), 7, '0', STR_PAD_LEFT),
                'password' => 'password',
                'status' => 'active',
            ]);
            $u->addresses()->create([
                'title' => 'Teslimat',
                'address' => "Senaryo sok. No {$i}",
                'latitude' => 38.42 + $i * 0.0004,
                'longitude' => 27.13 + $i * 0.0003,
            ]);
        }
    }
}
