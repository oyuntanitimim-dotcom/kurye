<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->string('compensation_type', 32)->default('none')->after('status')->index();
            $table->decimal('compensation_per_delivery', 10, 2)->nullable()->after('compensation_type');
            $table->decimal('compensation_monthly_salary', 10, 2)->nullable()->after('compensation_per_delivery');
            $table->decimal('compensation_per_km', 10, 4)->nullable()->after('compensation_monthly_salary');
            $table->text('compensation_notes')->nullable()->after('compensation_per_km');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('courier_payout_amount', 10, 2)->nullable()->after('restaurant_commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('courier_payout_amount');
        });

        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn([
                'compensation_type',
                'compensation_per_delivery',
                'compensation_monthly_salary',
                'compensation_per_km',
                'compensation_notes',
            ]);
        });
    }
};
