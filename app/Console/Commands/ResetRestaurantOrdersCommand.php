<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Integrations\Models\IntegrationExternalOrder;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetRestaurantOrdersCommand extends Command
{
    protected $signature = 'kurye:reset-restaurant-orders
        {--restaurant= : İşletme id (verilmezse --name ile aranır)}
        {--name=Konak Sof : İşletme adı içerir (LIKE %...% )}
        {--dry-run : Veritabanına yazmadan sadece say}';

    protected $description = 'Seçili işletmeye ait tüm siparişleri ve ilişkili harici pazar kayıtlarını siler (demo/test sıfırlama).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $namePattern = (string) $this->option('name');
        $restaurantIdOpt = $this->option('restaurant');

        if ($restaurantIdOpt !== null && $restaurantIdOpt !== '') {
            $restaurant = Restaurant::query()->find((int) $restaurantIdOpt);
        } else {
            $restaurant = Restaurant::query()
                ->where('name', 'like', '%'.trim($namePattern).'%')
                ->orderBy('id')
                ->first();
        }

        if ($restaurant === null) {
            $this->error('İşletme bulunamadı. --restaurant=id veya --name= ile deneyin.');

            return self::FAILURE;
        }

        $this->info("İşletme: #{$restaurant->id} {$restaurant->name}");

        $orderIds = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->orderBy('id')
            ->pluck('id');
        $count = $orderIds->count();

        if ($count === 0) {
            $this->info('Bu işletmeye ait sipariş yok.');

            return self::SUCCESS;
        }

        $this->line("Silinecek sipariş: {$count} (en küçük id: {$orderIds->first()}, en büyük: {$orderIds->last()})");
        if ($dryRun) {
            $this->warn('DRY RUN: silinmedi.');

            return self::SUCCESS;
        }

        $ext = IntegrationExternalOrder::query()->whereIn('order_id', $orderIds)->count();

        DB::transaction(function () use ($orderIds): void {
            IntegrationExternalOrder::query()->whereIn('order_id', $orderIds)->delete();
            Order::query()->whereIn('id', $orderIds)->delete();
        });

        $this->info("Tamam. integration_external_orders silinen satır: {$ext}, orders silinen: {$count}");

        return self::SUCCESS;
    }
}
