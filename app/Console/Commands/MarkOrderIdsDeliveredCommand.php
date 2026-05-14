<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderStateService;
use Illuminate\Console\Command;

class MarkOrderIdsDeliveredCommand extends Command
{
    protected $signature = 'kurye:mark-orders-delivered
        {ids* : Bir veya fazla sipariş id (örn. 1427 1428 veya 1427,1428 tek argümanda)}
        {--dry-run : Değişiklik yapmaz, sadece durumu gösterir}';

    protected $description = 'Verilen sipariş id’lerini toplu «teslim edildi» yapar (kurye atandı / alındı / yolda).';

    /** @var list<string> */
    private const DELIVERABLE = [
        'courier_assigned',
        'courier_accepted',
        'picked_up',
        'on_the_way',
    ];

    public function handle(OrderStateService $orderStateService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $idTokens = (array) $this->argument('ids');
        if ($idTokens === []) {
            $this->error('En az bir sipariş id verin. Örnek: php artisan kurye:mark-orders-delivered 1427 1428 1429');
            return self::FAILURE;
        }

        $ids = [];
        foreach ($idTokens as $t) {
            foreach (preg_split('/[\s,]+/', (string) $t, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $p) {
                $i = (int) $p;
                if ($i > 0) {
                    $ids[] = $i;
                }
            }
        }
        $ids = array_values(array_unique($ids));
        sort($ids);
        if ($ids === []) {
            $this->error('Geçerli id yok.');
            return self::FAILURE;
        }

        $this->info('Hedef id’ler: '.implode(', ', array_map('strval', $ids)));

        $orders = Order::query()->whereIn('id', $ids)->orderBy('id')->get()->keyBy('id');
        $missing = array_diff($ids, $orders->keys()->map(fn ($k) => (int) $k)->all());
        if ($missing !== []) {
            $this->error('Bulunamayan id: '.implode(', ', array_map('strval', $missing)));
            return self::FAILURE;
        }

        $ok = 0;
        $skipped = 0;
        /** @var Order $order */
        foreach ($ids as $id) {
            $order = $orders->get($id);
            if ($order === null) {
                continue;
            }
            if ($order->status === OrderStatus::Delivered->value) {
                $this->line("  #{$id}: zaten teslim edildi, atlandı.");
                $skipped++;
                continue;
            }
            if (! in_array($order->status, self::DELIVERABLE, true)) {
                $this->warn("  #{$id}: durum «{$order->status}» — kurye atandı / alındı / yolda değil, atlandı.");
                $skipped++;
                continue;
            }
            if ($dryRun) {
                $this->line("  [dry-run] #{$id}: {$order->status} → delivered");
                $ok++;
                continue;
            }
            $orderStateService->transition($order, OrderStatus::Delivered, [
                'message' => 'Toplu: CLI ile teslim edildi (deneme / operasyon kısayolu).',
                'event' => 'bulk_ids_delivered',
            ]);
            $this->line("  #{$id}: teslim edildi.");
            $ok++;
        }

        if ($dryRun) {
            $this->warn('DRY RUN: veritabanı güncellenmedi.');
        }
        $this->info("Özet: işlenen: {$ok}, atlandı/uygun değil: {$skipped}");

        return self::SUCCESS;
    }
}
