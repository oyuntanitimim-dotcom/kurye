<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Integrations\Models\IntegrationProductMap;
use App\Modules\Restaurants\Models\Product;
use Illuminate\Console\Command;

class ImportIntegrationProductMapsCommand extends Command
{
    protected $signature = 'kurye:import-product-maps
                            {--restaurant= : Restaurant ID}
                            {--provider=yemeksepeti : Provider key}
                            {--file= : CSV or JSON file path}
                            {--dry-run : Validate only, do not write DB}';

    protected $description = 'Toplu external SKU -> product_id esleme importu yapar.';

    public function handle(): int
    {
        $restaurantId = (int) ($this->option('restaurant') ?? 0);
        $provider = trim((string) $this->option('provider'));
        $file = trim((string) ($this->option('file') ?? ''));
        $dryRun = (bool) $this->option('dry-run');

        if ($restaurantId < 1) {
            $this->error('--restaurant zorunlu.');
            return self::FAILURE;
        }
        if ($provider === '') {
            $this->error('--provider zorunlu.');
            return self::FAILURE;
        }
        if ($file === '' || ! is_file($file)) {
            $this->error('--file zorunlu ve mevcut bir dosya olmali.');
            return self::FAILURE;
        }

        $rows = $this->readRows($file);
        if ($rows === []) {
            $this->warn('Import edilecek satir bulunamadi.');
            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;
        foreach ($rows as $idx => $row) {
            $line = $idx + 1;
            $externalSku = trim((string) ($row['external_sku'] ?? $row['sku'] ?? ''));
            $productId = (int) ($row['product_id'] ?? 0);

            if ($externalSku === '' || $productId < 1) {
                $this->warn("Satir {$line}: external_sku/product_id gecersiz, atlandi.");
                $fail++;
                continue;
            }

            $exists = Product::query()
                ->where('restaurant_id', $restaurantId)
                ->where('status', 'active')
                ->whereKey($productId)
                ->exists();

            if (! $exists) {
                $this->warn("Satir {$line}: product_id={$productId} restoranda aktif degil, atlandi.");
                $fail++;
                continue;
            }

            if (! $dryRun) {
                IntegrationProductMap::query()->updateOrCreate(
                    [
                        'restaurant_id' => $restaurantId,
                        'provider' => $provider,
                        'external_sku' => $externalSku,
                    ],
                    ['product_id' => $productId]
                );
            }
            $ok++;
        }

        $this->info(($dryRun ? 'DRY-RUN ' : '')."tamamlandi. Basarili: {$ok}, Hata/atlanan: {$fail}");
        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function readRows(string $file): array
    {
        $ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
        if ($ext === 'json') {
            $raw = file_get_contents($file);
            if ($raw === false) {
                return [];
            }
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                return [];
            }
            return array_values(array_filter($decoded, fn ($x) => is_array($x)));
        }

        // CSV varsayimi: header beklenir (external_sku,product_id)
        $h = fopen($file, 'rb');
        if ($h === false) {
            return [];
        }
        $header = fgetcsv($h);
        if (! is_array($header)) {
            fclose($h);
            return [];
        }
        $header = array_map(function ($x): string {
            $k = strtolower(trim((string) $x));
            return ltrim($k, "\xEF\xBB\xBF");
        }, $header);

        $out = [];
        while (($row = fgetcsv($h)) !== false) {
            if (! is_array($row)) {
                continue;
            }
            $assoc = [];
            foreach ($header as $i => $k) {
                if ($k === '') {
                    continue;
                }
                $assoc[$k] = $row[$i] ?? null;
            }
            if ($assoc !== []) {
                $out[] = $assoc;
            }
        }
        fclose($h);
        return $out;
    }
}

