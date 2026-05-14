<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bazı MySQL kurulumlarında önceki migration kaydı oluşup sütun eklenmemiş olabilir;
 * veya DDL yarım kalmış olabilir. Bu migration idempotent tamamlar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('integration_connections')) {
            return;
        }

        if (Schema::hasColumn('integration_connections', 'restaurant_id')) {
            $this->backfillNullRestaurantIds();
            $this->ensureRestaurantIdNotNull();
            $this->ensureNewUniqueAndFirmForeignKey();

            return;
        }

        try {
            Schema::table('integration_connections', function (Blueprint $table) {
                $table->dropForeign(['firm_id']);
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('integration_connections', function (Blueprint $table) {
                $table->dropUnique(['firm_id', 'provider']);
            });
        } catch (\Throwable) {
        }

        Schema::table('integration_connections', function (Blueprint $table) {
            $table->foreignId('restaurant_id')
                ->after('firm_id')
                ->nullable()
                ->constrained('restaurants')
                ->cascadeOnDelete();
        });

        $this->backfillNullRestaurantIds();
        $this->ensureRestaurantIdNotNull();
        $this->ensureNewUniqueAndFirmForeignKey();
    }

    public function down(): void
    {
        // Ana migration ile aynı geri alma; güvenli bırakıldı.
    }

    private function backfillNullRestaurantIds(): void
    {
        $rows = DB::table('integration_connections')
            ->select('id', 'firm_id', 'restaurant_id')
            ->get();

        foreach ($rows as $row) {
            if ($row->restaurant_id !== null) {
                continue;
            }
            $restaurantId = DB::table('restaurants')
                ->where('firm_id', $row->firm_id)
                ->orderBy('id')
                ->value('id');
            if ($restaurantId === null) {
                DB::table('integration_connections')->where('id', $row->id)->delete();
            } else {
                DB::table('integration_connections')->where('id', $row->id)->update([
                    'restaurant_id' => $restaurantId,
                ]);
            }
        }
    }

    private function ensureRestaurantIdNotNull(): void
    {
        if (! Schema::hasColumn('integration_connections', 'restaurant_id')) {
            return;
        }

        DB::table('integration_connections')->whereNull('restaurant_id')->delete();

        try {
            Schema::table('integration_connections', function (Blueprint $table) {
                $table->foreignId('restaurant_id')->nullable(false)->change();
            });
        } catch (\Throwable) {
        }
    }

    private function ensureNewUniqueAndFirmForeignKey(): void
    {
        try {
            Schema::table('integration_connections', function (Blueprint $table) {
                $table->unique(
                    ['firm_id', 'restaurant_id', 'provider'],
                    'integration_connections_firm_restaurant_provider_unique'
                );
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('integration_connections', function (Blueprint $table) {
                $table->foreign('firm_id')->references('id')->on('firms')->cascadeOnDelete();
            });
        } catch (\Throwable) {
        }
    }
};
