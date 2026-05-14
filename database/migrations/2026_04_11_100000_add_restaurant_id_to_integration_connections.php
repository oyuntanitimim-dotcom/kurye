<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('integration_connections')) {
            return;
        }

        if (Schema::hasColumn('integration_connections', 'restaurant_id')) {
            return;
        }

        // MySQL: firm_id FK can use (firm_id, provider) unique as supporting index; drop FK first.
        try {
            Schema::table('integration_connections', function (Blueprint $table) {
                $table->dropForeign(['firm_id']);
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('integration_connections', function (Blueprint $table) {
                $table->dropUnique(['firm_id', 'provider']);
            });
        } catch (\Throwable) {
        }

        Schema::table('integration_connections', function (Blueprint $table) {
            $table->foreignId('restaurant_id')
                ->after('firm_id')
                ->nullable()
                ->constrained('restaurants')
                ->cascadeOnDelete();
        });

        $rows = DB::table('integration_connections')->select('id', 'firm_id')->get();
        foreach ($rows as $row) {
            $restaurantId = DB::table('restaurants')
                ->where('firm_id', $row->firm_id)
                ->orderBy('id')
                ->value('id');
            if ($restaurantId === null) {
                DB::table('integration_connections')->where('id', $row->id)->delete();
            } else {
                DB::table('integration_connections')->where('id', $row->id)->update([
                    'restaurant_id' => $restaurantId,
                ]);
            }
        }

        Schema::table('integration_connections', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->nullable(false)->change();
        });

        try {
            Schema::table('integration_connections', function (Blueprint $table) {
                $table->unique(
                    ['firm_id', 'restaurant_id', 'provider'],
                    'integration_connections_firm_restaurant_provider_unique'
                );
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('integration_connections', function (Blueprint $table) {
                $table->foreign('firm_id')->references('id')->on('firms')->cascadeOnDelete();
            });
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        Schema::table('integration_connections', function (Blueprint $table) {
            $table->dropForeign(['firm_id']);
        });

        Schema::table('integration_connections', function (Blueprint $table) {
            $table->dropUnique('integration_connections_firm_restaurant_provider_unique');
        });

        Schema::table('integration_connections', function (Blueprint $table) {
            $table->dropForeign(['restaurant_id']);
            $table->dropColumn('restaurant_id');
        });

        Schema::table('integration_connections', function (Blueprint $table) {
            $table->unique(['firm_id', 'provider']);
        });

        Schema::table('integration_connections', function (Blueprint $table) {
            $table->foreign('firm_id')->references('id')->on('firms')->cascadeOnDelete();
        });
    }
};
