<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Users\Models\Address;
use App\Services\Geocoding\NominatimGeocoder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GeocodeMissingAddressesCommand extends Command
{
    protected $signature = 'kurye:geocode-missing-addresses
        {--limit=200 : En fazla kaç adres işlenecek}
        {--sleep_ms=1100 : Her istek arası bekleme (Nominatim için)}
        {--firm_id= : Sadece bu firmadaki kullanıcıların adresleri}
        {--dry-run : Veritabanına yazmadan simülasyon}';

    protected $description = 'Koordinatı olmayan adresleri metinden (Nominatim) geocode ederek doldurur.';

    public function handle(NominatimGeocoder $geocoder): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $sleepMs = max(0, (int) $this->option('sleep_ms'));
        $firmId = $this->option('firm_id') !== null ? (int) $this->option('firm_id') : null;
        $dryRun = (bool) $this->option('dry-run');

        $q = Address::query()
            ->whereNotNull('address')
            ->missingCoords()
            ->where('address', '!=', '');

        if ($firmId !== null && $firmId > 0) {
            $q->whereHas('user', fn ($uq) => $uq->where('firm_id', $firmId));
        }

        $targets = $q->orderBy('id')->limit($limit)->get();

        $this->info('Hedef adres: '.$targets->count().' (limit: '.$limit.')');
        if ($targets->isEmpty()) {
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY RUN: yazılmayacak.');
        }

        $updated = 0;
        $skipped = 0;
        $failed = 0;

        DB::beginTransaction();
        try {
            foreach ($targets as $a) {
                $addrText = trim((string) $a->address);
                $coords = $geocoder->geocodeFreeText($addrText);
                if ($coords === null) {
                    $failed++;
                } else {
                    $a->latitude = $coords['latitude'];
                    $a->longitude = $coords['longitude'];
                    $a->save();
                    $updated++;
                }

                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }

            if ($dryRun) {
                DB::rollBack();
                $skipped = $updated;
                $updated = 0;
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Tamam. Güncellenen: {$updated}, dry-run sayılan: {$skipped}, bulunamayan: {$failed}");

        return self::SUCCESS;
    }
}

