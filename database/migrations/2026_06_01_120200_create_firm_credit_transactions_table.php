<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firm_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('type', 32)->index(); // purchase, order_deduction, admin_adjustment, refund
            $table->integer('amount'); // pozitif: ekleme, negatif: dusum
            $table->integer('balance_after');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['firm_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firm_credit_transactions');
    }
};
