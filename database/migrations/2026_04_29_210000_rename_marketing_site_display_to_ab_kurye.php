<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketing_sites')) {
            return;
        }

        DB::table('marketing_sites')
            ->whereIn('name', ['Laravel', 'AbKurye', 'CourierSaaS'])
            ->update(['name' => 'Ab Kurye']);
    }

    public function down(): void
    {
        // Bilinçli: geri alınmaz.
    }
};
