<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_product_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('provider', 64)->index();
            $table->string('external_sku', 190);
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['restaurant_id', 'provider', 'external_sku'], 'int_prod_map_rest_prov_sku_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_product_maps');
    }
};
