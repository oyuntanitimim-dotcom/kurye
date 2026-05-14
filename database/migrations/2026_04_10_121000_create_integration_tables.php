<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained('firms')->cascadeOnDelete();
            $table->string('provider', 64)->index();
            $table->text('credentials_encrypted')->nullable();
            $table->json('settings_json')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['firm_id', 'provider']);
        });

        Schema::create('integration_external_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained('firms')->cascadeOnDelete();
            $table->string('provider', 64)->index();
            $table->string('external_order_id', 190);
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('last_payload_hash', 64)->nullable();
            $table->string('status', 32)->default('received')->index();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['firm_id', 'provider', 'external_order_id'], 'int_ext_ord_firm_prov_extid_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_external_orders');
        Schema::dropIfExists('integration_connections');
    }
};
