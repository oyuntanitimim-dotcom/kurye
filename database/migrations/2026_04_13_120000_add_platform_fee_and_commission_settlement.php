<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            $table->decimal('platform_fee_per_order', 10, 2)->default(0)->after('logo');
            $table->decimal('default_restaurant_commission_rate', 5, 2)->default(0)->after('platform_fee_per_order');
        });

        if (Schema::hasColumn('firms', 'commission_rate')) {
            DB::statement('UPDATE firms SET default_restaurant_commission_rate = commission_rate');
            Schema::table('firms', function (Blueprint $table) {
                $table->dropColumn('commission_rate');
            });
        }

        Schema::table('restaurants', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->nullable()->after('status');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('platform_fee_amount', 10, 2)->nullable()->after('discount_amount');
            $table->decimal('restaurant_commission_amount', 10, 2)->nullable()->after('platform_fee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['platform_fee_amount', 'restaurant_commission_amount']);
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });

        Schema::table('firms', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(0)->after('default_restaurant_commission_rate');
        });

        DB::statement('UPDATE firms SET commission_rate = default_restaurant_commission_rate');

        Schema::table('firms', function (Blueprint $table) {
            $table->dropColumn(['platform_fee_per_order', 'default_restaurant_commission_rate']);
        });
    }
};
