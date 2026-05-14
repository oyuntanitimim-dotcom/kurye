<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('opening_hours');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('tracking_token', 64)->nullable()->unique()->after('marketplace_provider');
            $table->timestamp('tracking_token_created_at')->nullable()->after('tracking_token');
            $table->timestamp('tracking_revoked_at')->nullable()->after('tracking_token_created_at');
        });

        Schema::create('order_dispatch_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('firm_id')->constrained('firms')->cascadeOnDelete();
            $table->foreignId('chosen_courier_id')->nullable()->constrained('couriers')->nullOnDelete();
            $table->json('candidates_json')->nullable();
            $table->string('trigger', 32);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['firm_id', 'created_at']);
        });

        if (Schema::hasTable('orders')) {
            DB::table('orders')->whereNull('tracking_token')->orderBy('id')->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('orders')->where('id', $row->id)->update([
                        'tracking_token' => Str::random(48),
                        'tracking_token_created_at' => $row->created_at ?? now()->toDateTimeString(),
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_dispatch_decisions');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tracking_token', 'tracking_token_created_at', 'tracking_revoked_at']);
        });

        Schema::table('firms', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
