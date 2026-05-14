<?php

declare(strict_types=1);

use App\Services\Marketing\MarketingHomeBlockSyncer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketing_pages')) {
            return;
        }

        app(MarketingHomeBlockSyncer::class)->sync(false);
    }

    public function down(): void
    {
        // Geri alınmaz: içerik birleştirmesi.
    }
};
