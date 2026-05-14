<?php

declare(strict_types=1);

use App\Services\Marketing\MarketingHomeBlockSyncer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;

return new class extends Migration
{
    public function up(): void
    {
        App::make(MarketingHomeBlockSyncer::class)->sync(false);
    }

    public function down(): void
    {
        // Bilinçli: yayınlanmış blokları geri almayız.
    }
};
