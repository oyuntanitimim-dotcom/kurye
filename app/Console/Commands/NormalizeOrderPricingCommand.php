<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Services\OrderSettlementService;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class NormalizeOrderPricingCommand extends Command
{
    protected $signature = 'kurye:normalize-order-pricing
        {--firm_domain=localhost : Firma domain (varsayılan: localhost)}
        {--restaurant_id= : Sadece bu işletme (opsiyonel)}
        {--from-id=0 : Bu sipariş id ve üstü}
        {--limit=0 : 0 = tümü}
        {--with-settlement : Teslim edilenlerde platform/işletme/kurye matrah alanlarını yeniden hesapla}
        {--dry-run : Veritabanına yazma}';

    protected $description = 'İndirimi sıfırlar, müşteri teslimat ücretini güncel işletme/firma ayarlarına çeker, kalem fiyatı + toplamı tutarlı hale getirir.';

    public function handle(OrderSettlementService $settlementService): int
    {
        $firmDomain = (string) $this->option('firm_domain');
        $restaurantId = $this->option('restaurant_id') !== null && $this->option('restaurant_id') !== ''
            ? (int) $this->option('restaurant_id')
            : null;
        $fromId = max(0, (int) $this->option('from-id'));
        $limit = max(0, (int) $this->option('limit'));
        $withSettlement = (bool) $this->option('with-settlement');
        $dryRun = (bool) $this->option('dry-run');

        $firm = Firm::query()->where('domain', $firmDomain)->first()
            ?? Firm::query()->orderBy('id')->first();
        if (! $firm) {
            $this->error('Firma bulunamadı.');

            return self::FAILURE;
        }

        $q = Order::query()
            ->where('firm_id', $firm->id)
            ->when($fromId > 0, fn ($qq) => $qq->where('id', '>=', $fromId))
            ->when($restaurantId !== null && $restaurantId > 0, fn ($qq) => $qq->where('restaurant_id', $restaurantId))
            ->orderBy('id');
        if ($limit > 0) {
            $q->limit($limit);
        }

        $ids = $q->pluck('id');
        $this->info("Firma: #{$firm->id} {$firm->name} — hedef sipariş: {$ids->count()}");

        if ($ids->isEmpty()) {
            $this->info('Sipariş yok.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY RUN: yazılmayacak.');
        }

        $restaurantCache = [];
        $updatedOrders = 0;
        $updatedItems = 0;
        $deliveredPatched = 0;

        DB::beginTransaction();
        try {
            $chunks = $ids->chunk(200);
            foreach ($chunks as $chunk) {
                /** @var Collection<int, Order> $orders */
                $orders = Order::query()
                    ->whereIn('id', $chunk->all())
                    ->get();

                foreach ($orders as $order) {
                    if ($order->restaurant_id) {
                        if (! array_key_exists($order->restaurant_id, $restaurantCache)) {
                            $restaurantCache[$order->restaurant_id] = Restaurant::query()
                                ->find($order->restaurant_id);
                        }
                    }
                    $restaurant = $order->restaurant_id
                        ? $restaurantCache[$order->restaurant_id] ?? null
                        : null;

                    $oldTotal = (float) $order->total_price;
                    $oldDel = (float) $order->delivery_fee;
                    $oldDisc = (float) $order->discount_amount;

                    $goodsSubtotal = max(0, round($oldTotal - $oldDel + $oldDisc, 2));
                    $newDel = $this->resolveCustomerDeliveryFee($firm, $restaurant);
                    $newDisc = 0.0;
                    $newTotal = max(0, round($goodsSubtotal + $newDel - $newDisc, 2));

                    $items = OrderItem::query()->where('order_id', $order->id)->orderBy('id')->get();
                    $lineSum = (float) $items->sum(fn (OrderItem $i) => (float) $i->price * (int) $i->quantity);

                    if (! $items->isEmpty() && $lineSum > 0) {
                        $tolerance = 0.02;
                        if (abs($lineSum - $oldTotal) < $tolerance) {
                            $targetSub = $goodsSubtotal;
                            $this->rescaleItemPrices($items, $targetSub, $dryRun, $updatedItems);
                        } elseif (abs($lineSum - $goodsSubtotal) < $tolerance) {
                            // Kalem toplamı zaten matrah; dokunma
                        } else {
                            $this->rescaleItemPrices($items, $goodsSubtotal, $dryRun, $updatedItems);
                        }
                    }

                    $settlementPatch = [];
                    if ($withSettlement && $order->status === OrderStatus::Delivered->value) {
                        $order->loadMissing('firm', 'restaurant', 'courier');
                        $settlementPatch = $settlementService->snapshotForDelivery($order);
                    }

                    if (! $dryRun) {
                        $updates = [
                            'delivery_fee' => number_format($newDel, 2, '.', ''),
                            'discount_amount' => number_format($newDisc, 2, '.', ''),
                            'total_price' => number_format($newTotal, 2, '.', ''),
                        ];
                        if ($settlementPatch !== []) {
                            $updates['platform_fee_amount'] = $settlementPatch['platform_fee_amount'];
                            $updates['restaurant_commission_amount'] = $settlementPatch['restaurant_commission_amount'];
                            $updates['courier_payout_amount'] = $settlementPatch['courier_payout_amount'];
                        }
                        Order::query()->whereKey($order->id)->update($updates);
                    }

                    $updatedOrders++;
                    if ($settlementPatch !== []) {
                        $deliveredPatched++;
                    }
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

            return self::FAILURE;
        }

        $this->info("Güncellenen sipariş: {$updatedOrders} (kalem fiyatı değişikliği: ~{$updatedItems} satır)");
        if ($withSettlement) {
            $this->line("Teslim edilen (settlement alanı yazıldı / dry-run dışı): {$deliveredPatched}");
        }

        return self::SUCCESS;
    }

    private function resolveCustomerDeliveryFee(Firm $firm, ?Restaurant $restaurant): float
    {
        if ($restaurant !== null && $restaurant->shop_delivery_fee !== null) {
            return max(0, round((float) $restaurant->shop_delivery_fee, 2));
        }
        $merged = $firm->mergedOperationSettings();

        return max(0, round((float) ($merged['default_delivery_fee'] ?? 0), 2));
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection<int, OrderItem> $items
     */
    private function rescaleItemPrices(Collection $items, float $targetSubtotal, bool $dryRun, int &$updatedItems): void
    {
        if ($items->isEmpty() || $targetSubtotal < 0) {
            return;
        }

        $lineSum = (float) $items->sum(fn (OrderItem $i) => (float) $i->price * (int) $i->quantity);
        if ($lineSum <= 0) {
            return;
        }

        $factor = $targetSubtotal / $lineSum;
        $running = 0.0;
        $list = $items->values();
        $last = $list->count() - 1;

        foreach ($list as $idx => $it) {
            $qty = max(1, (int) $it->quantity);
            if ($idx === $last) {
                $allocated = round($targetSubtotal - $running, 2);
                $newUnit = $allocated / $qty;
            } else {
                $newLine = round((float) $it->price * (float) $qty * $factor, 2);
                $running += $newLine;
                $newUnit = $newLine / $qty;
            }
            $newUnit = max(0, round($newUnit, 2));
            if (! $dryRun) {
                OrderItem::query()->whereKey($it->id)->update([
                    'price' => number_format($newUnit, 2, '.', ''),
                ]);
            }
            $updatedItems++;
        }
    }
}
