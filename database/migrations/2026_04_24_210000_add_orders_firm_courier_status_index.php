<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Otomatik atama: firma + kurye listesi + aktif teslimat durumları için toplu count sorgusu.
     */
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->index(['firm_id', 'courier_id', 'status'], 'orders_firm_courier_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropIndex('orders_firm_courier_status_idx');
            });
        }
    }
};
