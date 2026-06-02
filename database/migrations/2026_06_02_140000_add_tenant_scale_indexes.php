<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        // MySQL / MariaDB
        try {
            $db = DB::connection()->getDatabaseName();
            $row = DB::selectOne(
                'select 1 as ok from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ? limit 1',
                [$db, $table, $indexName]
            );

            return $row !== null;
        } catch (Throwable) {
            // Diğer driver’larda en azından çakışmaması için false dön.
            return false;
        }
    }

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // placeholder; index ekleme sonrası yapılacak
        });

        if (! $this->indexExists('users', 'users_firm_role_status_idx')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index(['firm_id', 'role_id', 'status'], 'users_firm_role_status_idx');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('users', 'users_firm_role_status_idx')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex('users_firm_role_status_idx');
            });
        }
    }
};

