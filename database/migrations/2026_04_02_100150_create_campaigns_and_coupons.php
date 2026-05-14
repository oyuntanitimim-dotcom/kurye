<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained('firms')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('min_order', 10, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->index(['firm_id', 'start_date', 'end_date']);
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained('firms')->cascadeOnDelete();
            $table->string('code', 64);
            $table->decimal('discount', 10, 2);
            $table->unsignedInteger('usage_limit')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->date('expire_date');
            $table->timestamps();

            $table->unique(['firm_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('campaigns');
    }
};
