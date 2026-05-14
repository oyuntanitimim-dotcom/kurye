<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderStateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarkInProgressDeliveredCommand extends Command
{
    protected $signature = 'kurye:mark-delivered
        {--firm_domain=localhost : Firma domain (default: localhost)}
        {--limit=500 : En fazla kaç sipariş güncellensin}
        {--only-on-the-way : Sadece «yolda» (on_the_way) siparişler; verilmezse atandı / alındı / yolda}
        {--dry-run : Veritabanına yazmadan simülasyon}';

    protected $description = 'Kurye atandı/alındı/yolda durumundaki siparişleri topluca teslim edildi yapar.';

    public function handle(OrderStateService $orderStateService): int
    {
        $firmDomain = (string) $this->option('firm_domain');
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $onlyOnTheWay = (bool) $this->option('only-on-the-way');

        $firm = Firm::query()->where('domain', $firmDomain)->first()
            ?? Firm::query()->orderBy('id')->first();
        if (! $firm) {
            $this->error('Firma bulunamadı.');
            return self::FAILURE;
        }

        $statuses = $onlyOnTheWay
            ? [OrderStatus::OnTheWay->value]
            : [
                OrderStatus::CourierAssigned->value,
                OrderStatus::CourierAccepted->value,
                OrderStatus::PickedUp->value,
                OrderStatus::OnTheWay->value,
            ];

        $targets = Order::query()
            ->where('firm_id', $firm->id)
            ->whereIn('status', $statuses)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $this->info("Firma: #{$firm->id} {$firm->name} — hedef sipariş: {$targets->count()}");
        if ($targets->isEmpty()) {
            $this->info('Güncellenecek sipariş yok.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY RUN: yazılmayacak.');
        }

        $updated = 0;
        DB::beginTransaction();
        try {
            foreach ($targets as $o) {
                $orderStateService->transition($o, OrderStatus::Delivered, [
                    'message' => 'Demo: rapor testi için toplu teslim.',
                    'event' => 'bulk_delivered',
                ]);
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

        $this->info("Tamam. Teslim edildi yapılan: {$updated}");
        return self::SUCCESS;
    }
}

