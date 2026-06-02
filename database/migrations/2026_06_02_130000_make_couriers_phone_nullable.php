<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('couriers', function (Blueprint $table): void {
            // Telefon panelde opsiyonel; canli ile yereldeki sema farkini giderir.
            $table->string('phone', 32)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table): void {
            $table->string('phone', 32)->nullable(false)->change();
        });
    }
};
