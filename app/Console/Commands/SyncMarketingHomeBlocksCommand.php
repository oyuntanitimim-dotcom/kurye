<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Marketing\MarketingHomeBlockSyncer;
use Illuminate\Console\Command;

class SyncMarketingHomeBlocksCommand extends Command
{
    protected $signature = 'marketing:sync-home-blocks {--dry-run : Değişiklik yapmadan raporla}';

    protected $description = 'Ana sayfa blocks_json\'a eksik blok tiplerini ekler; menü/footer yasal linklerini tamamlar.';

    public function handle(MarketingHomeBlockSyncer $syncer): int
    {
        $dry = (bool) $this->option('dry-run');
        $result = $syncer->sync($dry);

        if ($dry) {
            $this->info('Dry-run: yapılacak değişiklik özeti');
        }

        if ($result['appended_types'] !== []) {
            $this->info('Eklenecek blok tipleri: '.implode(', ', $result['appended_types']));
        } else {
            $this->line('Blok: eksik tip yok (zaten güncel).');
        }

        $this->line('Menü (İletişim): '.($result['menu_added'] ? 'eklenecek' : 'tamam'));
        $this->line('Footer (Yasal): '.($result['footer_added'] ? 'eklenecek' : 'tamam'));

        if (! $result['changed']) {
            $this->info('Özet: güncelleme gerekmedi.');

            return self::SUCCESS;
        }

        if ($dry) {
            $this->warn('Dry-run: veritabanı değiştirilmedi. Uygulamak için --dry-run olmadan çalıştırın.');

            return self::SUCCESS;
        }

        $this->info('Güncelleme tamamlandı.');

        return self::SUCCESS;
    }
}
