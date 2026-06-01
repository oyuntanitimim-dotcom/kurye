<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            $table->integer('credit_balance')->default(0)->after('status');
            $table->unsignedInteger('credits_per_order_override')->nullable()->after('credit_balance');
        });
    }

    public function down(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            $table->dropColumn(['credit_balance', 'credits_per_order_override']);
        });
    }
};
