<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;

class OrderPartitionManager
{
    public function ensureMonthlyPartition(string $table, string $yearMonth): void
    {
        // MySQL partition maintenance should be executed during low-traffic windows.
        DB::statement(sprintf(
            "ALTER TABLE `%s` PARTITION BY RANGE (TO_DAYS(created_at)) (PARTITION p%s VALUES LESS THAN (TO_DAYS('%s-01') + 32))",
            $table,
            str_replace('-', '', $yearMonth),
            $yearMonth
        ));
    }
}
