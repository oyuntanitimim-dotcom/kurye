<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Integrations\Models\IntegrationConnection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class YemeksepetiListConfiguredRestaurantsCommand extends Command
{
    protected $signature = 'kurye:yemeksepeti-list-configured
        {--active-only : Sadece is_active=true olanlari listele}';

    protected $description = 'Yemeksepeti icin configlenmis restaurantlari listeler (secret degerlerini yazmaz).';

    public function handle(): int
    {
        $q = IntegrationConnection::query()
            ->where('provider', 'yemeksepeti');

        if ($this->option('active-only')) {
            $q->where('is_active', true);
        }

        $rows = $q->get(['firm_id', 'restaurant_id', 'provider', 'is_active', 'settings_json', 'credentials_encrypted']);

        if ($rows->isEmpty()) {
            $this->info('Yemeksepeti icin configlenmis entegrasyon bulunamadi.');
            return self::SUCCESS;
        }

        $this->table(
            ['firm_id', 'restaurant_id', 'is_active', 'chain_id', 'vendor_id', 'has_client_id_secret'],
            $rows->map(function (IntegrationConnection $c): array {
                $settings = is_array($c->settings_json) ? $c->settings_json : [];
                $chainId = isset($settings['chain_id']) ? (string) $settings['chain_id'] : '';
                $vendorId = isset($settings['vendor_id']) ? (string) $settings['vendor_id'] : '';

                $hasCreds = false;
                if (is_string($c->credentials_encrypted) && $c->credentials_encrypted !== '') {
                    try {
                        $decoded = json_decode(Crypt::decryptString($c->credentials_encrypted), true);
                        $hasCreds = is_array($decoded)
                            && !empty($decoded['client_id'])
                            && !empty($decoded['client_secret']);
                    } catch (\Throwable) {
                        $hasCreds = false;
                    }
                }

                return [
                    (int) $c->firm_id,
                    (int) $c->restaurant_id,
                    $c->is_active ? 'true' : 'false',
                    $chainId !== '' ? $chainId : '—',
                    $vendorId !== '' ? $vendorId : '—',
                    $hasCreds ? 'true' : 'false',
                ];
            })->all()
        );

        return self::SUCCESS;
    }
}

