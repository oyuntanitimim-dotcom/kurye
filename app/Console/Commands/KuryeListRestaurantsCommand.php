<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Console\Command;

class KuryeListRestaurantsCommand extends Command
{
    protected $signature = 'kurye:list-restaurants {--limit=50 : Kac kayit listelensin}';

    protected $description = 'DB icinde restaurant kayitlarini listeler (secret yok).';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $rows = Restaurant::query()
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'name', 'firm_id']);

        if ($rows->isEmpty()) {
            $this->info('Restaurant kaydi bulunamadi.');
            return self::SUCCESS;
        }

        $this->table(['id', 'firm_id', 'name'], $rows->map(fn ($r) => [
            (int) $r->id,
            (int) $r->firm_id,
            (string) $r->name,
        ])->all());

        return self::SUCCESS;
    }
}

