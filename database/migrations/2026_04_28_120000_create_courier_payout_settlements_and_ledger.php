<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_payout_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained('firms')->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained('couriers')->cascadeOnDelete();
            $table->foreignId('recorded_by_user_id')->constrained('users');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('period_preset', 24)->nullable();
            $table->decimal('earnings_from_orders', 12, 2);
            $table->unsignedInteger('orders_count');
            $table->decimal('ledger_deductions', 12, 2);
            $table->decimal('ledger_credits', 12, 2);
            $table->decimal('net_paid', 12, 2);
            $table->string('payment_method', 32);
            $table->string('payment_reference')->nullable();
            $table->string('status', 16)->default('paid')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('courier_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained('firms')->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained('couriers')->cascadeOnDelete();
            $table->string('entry_kind', 32);
            $table->decimal('amount', 12, 2);
            $table->string('method', 32)->nullable();
            $table->string('reference')->nullable();
            $table->string('description')->nullable();
            $table->date('entry_date');
            $table->string('status', 16)->default('open')->index();
            $table->foreignId('settlement_id')->nullable()->constrained('courier_payout_settlements')->nullOnDelete();
            $table->timestamps();

            $table->index(['firm_id', 'courier_id', 'status', 'entry_date'], 'cpl_ledger_firm_cour_st_dt');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('courier_payout_settlement_id')
                ->nullable()
                ->after('courier_payout_amount')
                ->constrained('courier_payout_settlements')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['courier_payout_settlement_id']);
            $table->dropColumn('courier_payout_settlement_id');
        });

        Schema::dropIfExists('courier_ledger_entries');
        Schema::dropIfExists('courier_payout_settlements');
    }
};
