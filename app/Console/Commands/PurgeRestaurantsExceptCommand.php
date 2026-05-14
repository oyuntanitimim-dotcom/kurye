<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Integrations\Models\IntegrationConnection;
use App\Modules\Integrations\Models\IntegrationExternalOrder;
use App\Modules\Integrations\Models\IntegrationProductMap;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderDispatchDecision;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Models\OrderStatusHistory;
use App\Modules\Orders\Models\Review;
use App\Modules\Payments\Models\Payment;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\ProductImage;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Restaurants\Models\RestaurantCategory;
use App\Modules\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeRestaurantsExceptCommand extends Command
{
    protected $signature = 'kurye:purge-restaurants-except
        {--keep-restaurant-id= : Korunacak restoran id}
        {--keep-name=Konak Sof : Korunacak restoran adı (LIKE %...% )}
        {--dry-run : Silmeden sadece say}';

    protected $description = 'Restoran test verisi temizliği: seçili restoranı korur, diğer restoranları ve bağlı sipariş/ürün/entegrasyon kayıtlarını siler.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $keepIdOpt = $this->option('keep-restaurant-id');
        $keepName = (string) $this->option('keep-name');

        $keepRestaurant = null;
        if ($keepIdOpt !== null && $keepIdOpt !== '') {
            $keepRestaurant = Restaurant::query()->find((int) $keepIdOpt);
        } else {
            $keepRestaurant = Restaurant::query()
                ->where('name', 'like', '%'.trim($keepName).'%')
                ->orderBy('id')
                ->first();
        }

        if ($keepRestaurant === null) {
            $this->error('Korunacak restoran bulunamadı. --keep-restaurant-id= veya --keep-name= ile deneyin.');

            return self::FAILURE;
        }

        $deleteRestaurantIds = Restaurant::query()
            ->whereKeyNot($keepRestaurant->id)
            ->orderBy('id')
            ->pluck('id');

        $restaurantsCount = $deleteRestaurantIds->count();

        $orderIds = Order::query()
            ->whereIn('restaurant_id', $deleteRestaurantIds)
            ->orderBy('id')
            ->pluck('id');

        $ordersCount = $orderIds->count();

        $productIds = Product::query()
            ->whereIn('restaurant_id', $deleteRestaurantIds)
            ->orderBy('id')
            ->pluck('id');

        $productsCount = $productIds->count();

        $staffUserIds = User::query()
            ->whereIn('restaurant_id', $deleteRestaurantIds)
            ->orderBy('id')
            ->pluck('id');

        $staffCount = $staffUserIds->count();

        $this->info("Korunacak restoran: #{$keepRestaurant->id} {$keepRestaurant->name}");
        $this->line("Silinecek restoran: {$restaurantsCount}");
        $this->line("Silinecek ürün: {$productsCount}");
        $this->line("Silinecek sipariş (bu restoranlara ait): {$ordersCount}");
        $this->line("Silinecek restoran personeli (users.restaurant_id): {$staffCount}");

        if ($dry) {
            $this->warn('DRY RUN: silinmedi.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($deleteRestaurantIds, $orderIds, $productIds, $staffUserIds): void {
            // Orders and their dependents
            if ($orderIds->isNotEmpty()) {
                IntegrationExternalOrder::query()->whereIn('order_id', $orderIds)->delete();
                Payment::query()->whereIn('order_id', $orderIds)->delete();
                Review::query()->whereIn('order_id', $orderIds)->delete();
                OrderItem::query()->whereIn('order_id', $orderIds)->delete();
                OrderStatusHistory::query()->whereIn('order_id', $orderIds)->delete();
                OrderDispatchDecision::query()->whereIn('order_id', $orderIds)->delete();
                Order::query()->whereIn('id', $orderIds)->delete();
            }

            // Integrations tied to restaurant
            IntegrationConnection::query()->whereIn('restaurant_id', $deleteRestaurantIds)->delete();
            IntegrationProductMap::query()->whereIn('restaurant_id', $deleteRestaurantIds)->delete();

            // Product images + categories + products
            if ($productIds->isNotEmpty()) {
                ProductImage::query()->whereIn('product_id', $productIds)->delete();
            }
            RestaurantCategory::query()->whereIn('restaurant_id', $deleteRestaurantIds)->delete();
            Product::query()->whereIn('id', $productIds)->delete();

            // Staff users attached to those restaurants (demo/test cleanup)
            User::query()->whereIn('id', $staffUserIds)->delete();

            // Finally restaurants
            Restaurant::query()->whereIn('id', $deleteRestaurantIds)->delete();
        });

        $this->info('Tamam. Test restoranları ve bağlı veriler temizlendi.');

        return self::SUCCESS;
    }
}

