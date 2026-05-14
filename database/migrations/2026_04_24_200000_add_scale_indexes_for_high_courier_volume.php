<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Yüksek eşzamanlılık (binlerce kurye): konum güncellemesi ve operasyon sorguları için indeksler.
     */
    public function up(): void
    {
        if (Schema::hasTable('courier_locations')) {
            $keepIds = DB::table('courier_locations')
                ->selectRaw('MIN(id) as id')
                ->groupBy('courier_id')
                ->pluck('id')
                ->all();

            if ($keepIds !== []) {
                DB::table('courier_locations')->whereNotIn('id', $keepIds)->delete();
            }

            Schema::table('courier_locations', function (Blueprint $table): void {
                $table->unique('courier_id', 'courier_locations_courier_id_unique');
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->index(['firm_id', 'status', 'courier_id'], 'orders_firm_status_courier_idx');
                $table->index(['restaurant_id', 'created_at'], 'orders_restaurant_created_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropIndex('orders_firm_status_courier_idx');
                $table->dropIndex('orders_restaurant_created_idx');
            });
        }

        if (Schema::hasTable('courier_locations')) {
            Schema::table('courier_locations', function (Blueprint $table): void {
                $table->dropUnique('courier_locations_courier_id_unique');
            });
        }
    }
};
