<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Couriers\Models\Courier;
use App\Modules\Couriers\Models\CourierLocation;
use App\Modules\Integrations\Models\IntegrationExternalOrder;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderDispatchDecision;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Models\OrderStatusHistory;
use App\Modules\Orders\Models\Review;
use App\Modules\Payments\Models\Payment;
use App\Modules\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeCouriersExceptCommand extends Command
{
    protected $signature = 'kurye:purge-couriers-except
        {--keep-courier-id= : Korunacak kurye id (ör: 1)}
        {--keep-user-email= : Korunacak kurye kullanıcısının e-postası (ör: kurye@demo.local)}
        {--dry-run : Silmeden sadece say}';

    protected $description = 'Kurye test verisi temizliği: seçili kuryeyi korur, diğer kuryeleri ve onlara bağlı siparişleri siler.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $keepCourierIdOpt = $this->option('keep-courier-id');
        $keepUserEmailOpt = $this->option('keep-user-email');

        $keepCourierId = null;
        if ($keepCourierIdOpt !== null && $keepCourierIdOpt !== '') {
            $keepCourierId = (int) $keepCourierIdOpt;
        } elseif ($keepUserEmailOpt !== null && $keepUserEmailOpt !== '') {
            $u = User::query()->where('email', (string) $keepUserEmailOpt)->first();
            $keepCourierId = $u?->courierProfile?->id;
        }

        if ($keepCourierId === null) {
            $this->error('Korunacak kurye bulunamadı. --keep-courier-id= veya --keep-user-email= verin.');

            return self::FAILURE;
        }

        $keepCourier = Courier::query()->find($keepCourierId);
        if ($keepCourier === null) {
            $this->error("Kurye #{$keepCourierId} bulunamadı.");

            return self::FAILURE;
        }

        $deleteCourierIds = Courier::query()
            ->whereKeyNot($keepCourierId)
            ->orderBy('id')
            ->pluck('id');

        $deleteCouriersCount = $deleteCourierIds->count();

        $orderIds = Order::query()
            ->whereIn('courier_id', $deleteCourierIds)
            ->orderBy('id')
            ->pluck('id');

        $ordersCount = $orderIds->count();

        $this->info("Korunacak kurye: #{$keepCourier->id} {$keepCourier->name}");
        $this->line("Silinecek kurye: {$deleteCouriersCount}");
        $this->line("Silinecek sipariş (bu kuryelere atanmış): {$ordersCount}");

        if ($dry) {
            $this->warn('DRY RUN: silinmedi.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($deleteCourierIds, $orderIds): void {
            if ($orderIds->isNotEmpty()) {
                IntegrationExternalOrder::query()->whereIn('order_id', $orderIds)->delete();
                Payment::query()->whereIn('order_id', $orderIds)->delete();
                Review::query()->whereIn('order_id', $orderIds)->delete();
                OrderItem::query()->whereIn('order_id', $orderIds)->delete();
                OrderStatusHistory::query()->whereIn('order_id', $orderIds)->delete();
                OrderDispatchDecision::query()->whereIn('order_id', $orderIds)->delete();
                Order::query()->whereIn('id', $orderIds)->delete();
            }

            if ($deleteCourierIds->isNotEmpty()) {
                CourierLocation::query()->whereIn('courier_id', $deleteCourierIds)->delete();
                Courier::query()->whereIn('id', $deleteCourierIds)->delete();
            }
        });

        $this->info('Tamam. Test kuryeleri ve siparişleri temizlendi.');

        return self::SUCCESS;
    }
}

