<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Console\Command;

class ExportIntegrationProductMapTemplateCommand extends Command
{
    protected $signature = 'kurye:export-product-maps-template
                            {--restaurant= : Restaurant ID}
                            {--provider=yemeksepeti : Provider key}
                            {--output= : Output csv path (default: storage/app/product-map-template-{restaurant}.csv)}';

    protected $description = 'External SKU mapping icin CSV sablonu uretir.';

    public function handle(): int
    {
        $restaurantId = (int) ($this->option('restaurant') ?? 0);
        $provider = trim((string) $this->option('provider'));
        if ($restaurantId < 1) {
            $this->error('--restaurant zorunlu.');
            return self::FAILURE;
        }

        $restaurant = Restaurant::query()->find($restaurantId);
        if ($restaurant === null) {
            $this->error("Restoran bulunamadi: {$restaurantId}");
            return self::FAILURE;
        }

        $output = trim((string) ($this->option('output') ?? ''));
        if ($output === '') {
            $safeProvider = preg_replace('/[^a-z0-9_\-]+/i', '-', $provider) ?: 'provider';
            $output = storage_path("app/product-map-template-{$restaurantId}-{$safeProvider}.csv");
        }

        $rows = Product::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'price']);

        $dir = dirname($output);
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $h = fopen($output, 'wb');
        if ($h === false) {
            $this->error('CSV dosyasi olusturulamadi: '.$output);
            return self::FAILURE;
        }

        // UTF-8 BOM for Excel compatibility
        fwrite($h, "\xEF\xBB\xBF");
        fputcsv($h, ['external_sku', 'product_id', 'product_name', 'price'], ',');
        foreach ($rows as $p) {
            fputcsv($h, ['', (int) $p->id, (string) $p->name, (string) $p->price], ',');
        }
        fclose($h);

        $this->info("Sablon olusturuldu: {$output}");
        $this->line('Sonraki adim: external_sku kolonunu doldurup import komutunu calistirin.');
        $this->line("Import: php artisan kurye:import-product-maps --restaurant={$restaurantId} --provider={$provider} --file=\"{$output}\"");
        return self::SUCCESS;
    }
}

