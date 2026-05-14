<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Models\Firm;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCourierLocationsCommand extends Command
{
    protected $signature = 'kurye:backfill-courier-locations
        {--firm_domain=localhost : Firma domain (default: localhost)}
        {--radius=0.02 : Dağılım yarıçapı (derece cinsinden, yaklaşık) }
        {--touch-all : Mevcut konumların updated_at alanını şimdi yap}
        {--dry-run : Veritabanına yazmadan simülasyon}';

    protected $description = 'Konumu olmayan aktif kuryelere demo konumu yazar (otomatik atama havuzu için).';

    public function handle(): int
    {
        $firmDomain = (string) $this->option('firm_domain');
        $radius = max(0.001, (float) $this->option('radius'));
        $touchAll = (bool) $this->option('touch-all');
        $dryRun = (bool) $this->option('dry-run');

        $firm = Firm::query()->where('domain', $firmDomain)->first()
            ?? Firm::query()->orderBy('id')->first();
        if (! $firm) {
            $this->error('Firma bulunamadı.');
            return self::FAILURE;
        }

        $anchor = Restaurant::query()
            ->where('firm_id', $firm->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('id')
            ->first(['latitude', 'longitude']);

        $baseLat = $anchor?->latitude !== null ? (float) $anchor->latitude : 38.422;
        $baseLng = $anchor?->longitude !== null ? (float) $anchor->longitude : 27.131;

        $couriers = Courier::query()
            ->where('firm_id', $firm->id)
            ->where('status', 'active')
            ->with('location')
            ->get();

        $missing = $couriers->filter(fn (Courier $c) => $c->location === null)->values();
        $this->info("Firma: #{$firm->id} {$firm->name} — aktif kurye: {$couriers->count()}, konumsuz: {$missing->count()}");

        if ($missing->isEmpty() && ! $touchAll) {
            $this->info('Eksik konum yok. updated_at yenilemek için: --touch-all');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY RUN: yazılmayacak.');
        }

        $written = 0;
        $touched = 0;
        DB::beginTransaction();
        try {
            if ($touchAll) {
                $ids = $couriers
                    ->filter(fn (Courier $c) => $c->location !== null)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if ($ids !== []) {
                    $touched = DB::table('courier_locations')
                        ->whereIn('courier_id', $ids)
                        ->update(['updated_at' => now()]);
                }
            }

            foreach ($missing as $c) {
                $lat = $baseLat + (random_int(-1000, 1000) / 1000) * $radius;
                $lng = $baseLng + (random_int(-1000, 1000) / 1000) * $radius;

                DB::table('courier_locations')->insert([
                    'courier_id' => $c->id,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'updated_at' => now(),
                ]);
                $written++;
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

        $this->info("Tamam. Yazılan konum: {$written}, tazelenen konum: {$touched}");
        return self::SUCCESS;
    }
}

