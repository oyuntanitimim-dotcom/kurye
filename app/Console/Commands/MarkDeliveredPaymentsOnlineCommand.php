<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use Illuminate\Console\Command;

class MarkDeliveredPaymentsOnlineCommand extends Command
{
    protected $signature = 'kurye:mark-delivered-payments-online
        {--firm_id=0 : Sadece bu firma (0 = tümü)}
        {--dry-run : Veritabanına yazma}';

    protected $description = 'Teslim edilmiş siparişlerin ödeme yöntemini "online" yapar (demo: finans filtresiyle uyum).';

    public function handle(): int
    {
        $firmId = max(0, (int) $this->option('firm_id'));
        $dryRun = (bool) $this->option('dry-run');

        $q = Order::query()
            ->where('status', OrderStatus::Delivered->value)
            ->where('payment_method', '!=', 'online');

        if ($firmId > 0) {
            $q->where('firm_id', $firmId);
        }

        $count = (clone $q)->count();
        $this->info("Güncellenecek teslim sipariş: {$count}");

        if ($count === 0) {
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY RUN: yazılmayacak.');

            return self::SUCCESS;
        }

        $updated = Order::query()
            ->where('status', OrderStatus::Delivered->value)
            ->where('payment_method', '!=', 'online')
            ->when($firmId > 0, fn ($qq) => $qq->where('firm_id', $firmId))
            ->update(['payment_method' => 'online']);

        $this->info("Tamam. Güncellenen: {$updated}");

        return self::SUCCESS;
    }
}
