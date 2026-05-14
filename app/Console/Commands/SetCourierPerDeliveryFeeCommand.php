<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CourierCompensationType;
use App\Enums\OrderStatus;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderSettlementService;
use Illuminate\Console\Command;

class SetCourierPerDeliveryFeeCommand extends Command
{
    protected $signature = 'kurye:set-courier-per-delivery-fee
        {--amount=50 : Teslim başı ücret (₺)}
        {--firm_id=0 : Sadece bu firma id (0 = tüm firmalar)}
        {--no-payout-refresh : Teslim siparişlerinde courier_payout_amount güncellemesini atla}
        {--dry-run : Veritabanına yazma}';

    protected $description = '"Teslim başı sabit ücret" modelindeki kuryelerin paket başı ücretini aynı tutara çeker; teslim edilmiş siparişlerde kurye ödemesi alanını güncel kurye ücretine göre yeniler.';

    public function handle(OrderSettlementService $settlementService): int
    {
        $amount = max(0, round((float) $this->option('amount'), 2));
        $firmId = max(0, (int) $this->option('firm_id'));
        $noPayoutRefresh = (bool) $this->option('no-payout-refresh');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN: yazılmayacak.');
        }

        $type = CourierCompensationType::PerDelivery->value;

        $q = Courier::query()->where('compensation_type', $type);
        if ($firmId > 0) {
            $q->where('firm_id', $firmId);
        }

        $courierCount = (clone $q)->count();
        $this->info("Hedef: compensation_type={$type}, kurye sayısı: {$courierCount}, tutar: {$amount} ₺");

        if (! $dryRun) {
            $q->update(['compensation_per_delivery' => number_format($amount, 2, '.', '')]);
        }

        $payoutUpdated = 0;
        if (! $noPayoutRefresh) {
            $oq = Order::query()
                ->where('status', OrderStatus::Delivered->value)
                ->whereNotNull('courier_id');
            if ($firmId > 0) {
                $oq->where('firm_id', $firmId);
            }

            $orderCount = (clone $oq)->count();
            $this->line("Teslim sipariş (kuryeli) güncelleme: {$orderCount} kayıt.");

            if (! $dryRun) {
                $oq->orderBy('id')->chunkById(250, function ($orders) use ($settlementService, &$payoutUpdated): void {
                    foreach ($orders as $order) {
                        $order->loadMissing('firm', 'restaurant', 'courier');
                        $snap = $settlementService->snapshotForDelivery($order);
                        Order::query()->whereKey($order->id)->update([
                            'courier_payout_amount' => $snap['courier_payout_amount'],
                        ]);
                        $payoutUpdated++;
                    }
                });
            }
        }

        if ($dryRun) {
            $this->info('DRY RUN: kurye ve sipariş güncellemesi yapılmadı.');
        } else {
            $this->info("Kurye kaydı güncellendi: {$courierCount}, courier_payout_amount yenilenen sipariş: {$payoutUpdated}");
        }

        return self::SUCCESS;
    }
}
