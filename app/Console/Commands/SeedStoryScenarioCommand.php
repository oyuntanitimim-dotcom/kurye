<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Models\OrderStatusHistory;
use App\Modules\Orders\Services\AutoDispatchService;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedStoryScenarioCommand extends Command
{
    protected $signature = 'kurye:seed-story
        {--firm_domain=localhost : Firma domain (default: localhost)}
        {--count=12 : Oluşturulacak hikayeli sipariş sayısı}
        {--auto=4 : Otomatik atama ile atanacak hazır sipariş sayısı}
        {--dry-run : Veritabanına yazmadan simülasyon}';

    protected $description = 'Gerçekçi senaryo: notlar + durum mesajları + otomatik/manuel atama test siparişleri üretir.';

    public function handle(AutoDispatchService $autoDispatchService): int
    {
        $firmDomain = (string) $this->option('firm_domain');
        $count = max(5, (int) $this->option('count'));
        $autoCount = max(0, (int) $this->option('auto'));
        $dryRun = (bool) $this->option('dry-run');

        $firm = Firm::query()->where('domain', $firmDomain)->first()
            ?? Firm::query()->orderBy('id')->first();
        if (! $firm) {
            $this->error('Firma bulunamadı. Önce `php artisan db:seed` çalıştırın.');
            return self::FAILURE;
        }

        $restaurantIds = Restaurant::query()
            ->where('firm_id', $firm->id)
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->pluck('id');
        if ($restaurantIds->isEmpty()) {
            $this->error('Firma altında koordinatlı işletme yok (otomatik atama için latitude/longitude gerekiyor).');
            return self::FAILURE;
        }

        $couriers = Courier::query()
            ->where('firm_id', $firm->id)
            ->where('status', 'active')
            ->with('location')
            ->get();
        if ($couriers->isEmpty()) {
            $this->error('Aktif kurye yok. Önce `php artisan kurye:seed-scenarios --couriers=5 --orders=0` çalıştırın.');
            return self::FAILURE;
        }

        $customerRoleId = Role::query()->where('name', Role::CUSTOMER)->value('id');
        if (! $customerRoleId) {
            $this->error('customer role yok. Önce `php artisan db:seed` çalıştırın.');
            return self::FAILURE;
        }

        $productsByRestaurant = Product::query()
            ->whereIn('restaurant_id', $restaurantIds)
            ->pluck('id', 'restaurant_id')
            ->groupBy(fn ($productId, $restaurantId) => $restaurantId);

        $baseStories = $this->storyTemplates();
        $stories = [];
        for ($i = 0; $i < $count; $i++) {
            $tpl = $baseStories[$i % count($baseStories)];
            // Aynı senaryoyu farklı müşteri adıyla çoğalt ama rastgele finans üretme.
            $tpl['customer_name'] = $tpl['customer_name'].' '.($i + 1);
            $stories[] = $tpl;
        }

        if ($dryRun) {
            $this->warn('DRY RUN: veritabanına yazılmayacak.');
        }

        $created = 0;
        $autoAssigned = 0;

        DB::beginTransaction();
        try {
            foreach ($stories as $idx => $tpl) {
                $i = $idx + 1;
                $cust = $this->createCustomer((int) $firm->id, (int) $customerRoleId, $tpl['customer_name'], $tpl['customer_phone'], $i);
                $addr = $cust->addresses()->first();

                $restaurantId = (int) $restaurantIds->random();

                $createdAt = now()->subHours(random_int(1, 48))->subMinutes(random_int(0, 55));
                $status = (string) $tpl['final_status'];

                $order = Order::query()->create([
                    'firm_id' => $firm->id,
                    'user_id' => $cust->id,
                    'restaurant_id' => $restaurantId,
                    'courier_id' => null,
                    'delivery_address_id' => $addr?->id,
                    'status' => $status,
                    'total_price' => (float) $tpl['total'],
                    'delivery_fee' => (float) $tpl['delivery_fee'],
                    'discount_amount' => (float) $tpl['discount'],
                    'platform_fee_amount' => (float) ($firm->platform_fee_per_order ?? 0),
                    'restaurant_commission_amount' => (float) ($firm->default_restaurant_fee_per_delivery ?? 0),
                    'courier_payout_amount' => null,
                    'payment_method' => (string) $tpl['payment_method'],
                    'campaign_id' => null,
                    'coupon_id' => null,
                    'notes' => (string) $tpl['order_note'],
                ]);

                // timestamps
                $order->created_at = $createdAt;
                $order->updated_at = (clone $createdAt)->addMinutes(random_int(8, 240));
                $order->save();

                // one item for nicer detail
                $pids = $productsByRestaurant[(string) $restaurantId] ?? null;
                if ($pids && $pids->isNotEmpty()) {
                    $pid = (int) $pids->random();
                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $pid,
                        'price' => (float) $tpl['total'],
                        'quantity' => 1,
                        'product_name' => (string) $tpl['item_name'],
                    ]);
                }

                // status timeline
                $this->seedTimeline($order, $createdAt, $tpl['timeline']);

                // For some "ready + no courier" orders, run auto dispatch to test pool.
                if ($autoAssigned < $autoCount && $status === OrderStatus::Ready->value && $order->courier_id === null) {
                    $res = $autoDispatchService->dispatchOrder($order, 'story_manual', null);
                    if (($res['ok'] ?? false) === true) {
                        $autoAssigned++;
                    }
                }

                $created++;
            }

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

        $this->info("Tamam. Hikayeli sipariş: {$created}, otomatik atanan: {$autoAssigned}");
        $this->line('Test: /firma/siparisler?awaiting_courier=1 (hazır + kuryesiz) → Otomatik ata veya manuel Ata.');

        return self::SUCCESS;
    }

    private function createCustomer(int $firmId, int $customerRoleId, string $name, string $phone, int $i): User
    {
        $suffix = Str::lower(Str::random(6));
        $email = "story-musteri-{$firmId}-{$suffix}@seed.local";

        $u = User::query()->create([
            'firm_id' => $firmId,
            'role_id' => $customerRoleId,
            'restaurant_id' => null,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => 'password',
            'status' => 'active',
        ]);

        $u->addresses()->create([
            'title' => 'Ev',
            'address' => 'Senaryo adres '.$i.' — Kat '.random_int(1, 6).', Daire '.random_int(1, 20),
            'latitude' => 38.42 + ($i * 0.0007),
            'longitude' => 27.13 + ($i * 0.0006),
        ]);

        return $u;
    }

    /**
     * @param list<array{status:string, minutes:int, message?:string}> $timeline
     */
    private function seedTimeline(Order $order, \Carbon\Carbon $base, array $timeline): void
    {
        $t = (clone $base);
        foreach ($timeline as $step) {
            $t = (clone $t)->addMinutes((int) $step['minutes']);
            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => (string) $step['status'],
                'meta' => empty($step['message']) ? null : ['message' => (string) $step['message']],
                'created_at' => $t,
            ]);
        }
    }

    /** @return list<array<string,mixed>> */
    private function storyTemplates(): array
    {
        return [
            [
                'customer_name' => 'Ayşe Yılmaz',
                'customer_phone' => '0555 120 33 44',
                'payment_method' => 'cash_on_delivery',
                'total' => 245.50,
                'delivery_fee' => 15.00,
                'discount' => 0.00,
                'item_name' => 'Karışık pide + Ayran',
                'order_note' => 'Kapıda nakit. Bebek uyuyor, zili çalmayın; arayın.',
                'final_status' => OrderStatus::Ready->value,
                'timeline' => [
                    ['status' => OrderStatus::Pending->value, 'minutes' => 0, 'message' => 'Müşteri: zili çalma, ara.'],
                    ['status' => OrderStatus::Accepted->value, 'minutes' => 3, 'message' => 'Restoran: onaylandı.'],
                    ['status' => OrderStatus::Preparing->value, 'minutes' => 8, 'message' => 'Restoran: hazırlanıyor.'],
                    ['status' => OrderStatus::Ready->value, 'minutes' => 14, 'message' => 'Restoran: hazır, kurye bekleniyor.'],
                ],
            ],
            [
                'customer_name' => 'Mehmet Demir',
                'customer_phone' => '0555 220 10 10',
                'payment_method' => 'card',
                'total' => 409.00,
                'delivery_fee' => 18.00,
                'discount' => 0.00,
                'item_name' => 'Burger menü',
                'order_note' => 'Apartman kapısı şifreli: 1234#',
                'final_status' => OrderStatus::OnTheWay->value,
                'timeline' => [
                    ['status' => OrderStatus::Pending->value, 'minutes' => 0],
                    ['status' => OrderStatus::Accepted->value, 'minutes' => 2, 'message' => 'Restoran: sipariş alındı.'],
                    ['status' => OrderStatus::Preparing->value, 'minutes' => 10],
                    ['status' => OrderStatus::Ready->value, 'minutes' => 12, 'message' => 'Restoran: hazır.'],
                    ['status' => OrderStatus::CourierAssigned->value, 'minutes' => 2, 'message' => 'Sistem: kurye atandı.'],
                    ['status' => OrderStatus::PickedUp->value, 'minutes' => 6, 'message' => 'Kurye: siparişi aldım.'],
                    ['status' => OrderStatus::OnTheWay->value, 'minutes' => 4, 'message' => 'Kurye: yoldayım (trafik var).'],
                ],
            ],
            [
                'customer_name' => 'Elif Kaya',
                'customer_phone' => '0555 330 22 11',
                'payment_method' => 'card',
                'total' => 179.00,
                'delivery_fee' => 12.50,
                'discount' => 0.00,
                'item_name' => 'Kahve + Tatlı',
                'order_note' => 'Lütfen plastik çatal istemiyorum.',
                'final_status' => OrderStatus::Delivered->value,
                'timeline' => [
                    ['status' => OrderStatus::Pending->value, 'minutes' => 0],
                    ['status' => OrderStatus::Accepted->value, 'minutes' => 1],
                    ['status' => OrderStatus::Preparing->value, 'minutes' => 6],
                    ['status' => OrderStatus::Ready->value, 'minutes' => 10],
                    ['status' => OrderStatus::CourierAssigned->value, 'minutes' => 2, 'message' => 'Sistem: en yakın kurye seçildi.'],
                    ['status' => OrderStatus::PickedUp->value, 'minutes' => 5],
                    ['status' => OrderStatus::OnTheWay->value, 'minutes' => 6],
                    ['status' => OrderStatus::Delivered->value, 'minutes' => 7, 'message' => 'Kurye: teslim edildi.'],
                ],
            ],
            [
                'customer_name' => 'Can Er',
                'customer_phone' => '0555 410 55 66',
                'payment_method' => 'cash_on_delivery',
                'total' => 129.90,
                'delivery_fee' => 10.00,
                'discount' => 0.00,
                'item_name' => 'Market sepeti',
                'order_note' => 'Poşetleri kapıya bırakabilirsiniz.',
                'final_status' => OrderStatus::Cancelled->value,
                'timeline' => [
                    ['status' => OrderStatus::Pending->value, 'minutes' => 0],
                    ['status' => OrderStatus::Accepted->value, 'minutes' => 4],
                    ['status' => OrderStatus::Cancelled->value, 'minutes' => 6, 'message' => 'Müşteri: yanlış adres, iptal.'],
                ],
            ],
            [
                'customer_name' => 'Zeynep Ak',
                'customer_phone' => '0555 515 88 77',
                'payment_method' => 'card',
                'total' => 284.00,
                'delivery_fee' => 15.00,
                'discount' => 0.00,
                'item_name' => 'Pizza',
                'order_note' => 'Yanında ketçap-mayonez rica ederim.',
                'final_status' => OrderStatus::Preparing->value,
                'timeline' => [
                    ['status' => OrderStatus::Pending->value, 'minutes' => 0],
                    ['status' => OrderStatus::Accepted->value, 'minutes' => 2],
                    ['status' => OrderStatus::Preparing->value, 'minutes' => 7, 'message' => 'Restoran: yoğunluk var, 15 dk.'],
                ],
            ],
            [
                'customer_name' => 'Murat Şahin',
                'customer_phone' => '0555 606 12 12',
                'payment_method' => 'cash_on_delivery',
                'total' => 315.00,
                'delivery_fee' => 20.00,
                'discount' => 0.00,
                'item_name' => 'Kebap',
                'order_note' => 'Lütfen acı olsun.',
                'final_status' => OrderStatus::Ready->value,
                'timeline' => [
                    ['status' => OrderStatus::Pending->value, 'minutes' => 0],
                    ['status' => OrderStatus::Accepted->value, 'minutes' => 2],
                    ['status' => OrderStatus::Preparing->value, 'minutes' => 9],
                    ['status' => OrderStatus::Ready->value, 'minutes' => 13, 'message' => 'Restoran: hazır, kurye bekleniyor.'],
                ],
            ],
        ];
    }
}

