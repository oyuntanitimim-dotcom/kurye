<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            $table->decimal('default_restaurant_fee_per_delivery', 10, 2)->default(0)->after('platform_fee_per_order');
        });

        Schema::table('firms', function (Blueprint $table) {
            $table->dropColumn('default_restaurant_commission_rate');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->decimal('fee_per_delivery', 10, 2)->nullable()->after('status');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->nullable()->after('status');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('fee_per_delivery');
        });

        Schema::table('firms', function (Blueprint $table) {
            $table->decimal('default_restaurant_commission_rate', 5, 2)->default(0)->after('platform_fee_per_order');
        });

        Schema::table('firms', function (Blueprint $table) {
            $table->dropColumn('default_restaurant_fee_per_delivery');
        });
    }
};
