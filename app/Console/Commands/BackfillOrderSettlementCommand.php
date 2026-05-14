<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderStatusHistory;
use App\Modules\Orders\Services\OrderSettlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillOrderSettlementCommand extends Command
{
    protected $signature = 'kurye:backfill-settlement
        {--firm_domain=localhost : Firma domain (default: localhost)}
        {--restaurant_id= : Sadece bu işletme (opsiyonel)}
        {--force : Null olmasa bile yeniden hesapla}
        {--limit=2000 : En fazla kaç sipariş}
        {--dry-run : Veritabanına yazmadan simülasyon}';

    protected $description = 'Delivered siparişlerde boş settlement alanlarını doldurur (platform/işletme/kurye).';

    public function handle(OrderSettlementService $settlementService): int
    {
        $firmDomain = (string) $this->option('firm_domain');
        $restaurantId = $this->option('restaurant_id') !== null ? (int) $this->option('restaurant_id') : null;
        $force = (bool) $this->option('force');
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $firm = Firm::query()->where('domain', $firmDomain)->first()
            ?? Firm::query()->orderBy('id')->first();
        if (! $firm) {
            $this->error('Firma bulunamadı.');
            return self::FAILURE;
        }

        $q = Order::query()
            ->where('firm_id', $firm->id)
            ->where('status', OrderStatus::Delivered->value)
            ->when(! $force, function ($qq) {
                $qq->where(function ($q2) {
                    $q2->whereNull('platform_fee_amount')
                        ->orWhereNull('restaurant_commission_amount')
                        ->orWhereNull('courier_payout_amount');
                });
            })
            ->orderBy('id')
            ->limit($limit);

        if ($restaurantId !== null && $restaurantId > 0) {
            $q->where('restaurant_id', $restaurantId);
        }

        $targets = $q->get();
        $this->info("Firma: #{$firm->id} {$firm->name} — hedef delivered: {$targets->count()}");

        if ($targets->isEmpty()) {
            $this->info('Backfill gerektiren sipariş yok.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY RUN: yazılmayacak.');
        }

        $updated = 0;
        DB::beginTransaction();
        try {
            foreach ($targets as $o) {
                $snap = $settlementService->snapshotForDelivery($o);

                $updates = [];
                if ($force || $o->platform_fee_amount === null) {
                    $updates['platform_fee_amount'] = $snap['platform_fee_amount'];
                }
                if ($force || $o->restaurant_commission_amount === null) {
                    $updates['restaurant_commission_amount'] = $snap['restaurant_commission_amount'];
                }
                if ($force || $o->courier_payout_amount === null) {
                    $updates['courier_payout_amount'] = $snap['courier_payout_amount'];
                }

                if ($updates === []) {
                    continue;
                }

                if (! $dryRun) {
                    Order::query()->whereKey($o->id)->update($updates);
                    OrderStatusHistory::query()->create([
                        'order_id' => $o->id,
                        'status' => OrderStatus::Delivered->value,
                        'meta' => [
                            'event' => 'settlement_backfill',
                            'platform_fee_amount' => $updates['platform_fee_amount'] ?? null,
                            'restaurant_commission_amount' => $updates['restaurant_commission_amount'] ?? null,
                            'courier_payout_amount' => $updates['courier_payout_amount'] ?? null,
                        ],
                        'created_at' => now(),
                    ]);
                }

                $updated++;
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

        $this->info("Tamam. Güncellenen sipariş: {$updated}");
        return self::SUCCESS;
    }
}

