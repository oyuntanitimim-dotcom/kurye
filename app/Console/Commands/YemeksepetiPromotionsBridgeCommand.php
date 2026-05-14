<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Integrations\Models\IntegrationConnection;
use App\Services\Integrations\YemeksepetiPartnerApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class YemeksepetiPromotionsBridgeCommand extends Command
{
    protected $signature = 'kurye:yemeksepeti-promotions-bridge
        {--restaurant= : Restaurant ID}
        {--payload-file= : JSON payload file path}
        {--job-id= : Existing remote job id (status check)}
        {--base-url= : API base URL override (default https://yemeksepeti.partner.deliveryhero.io)}
        {--client-id= : Client ID override}
        {--client-secret= : Client Secret override}
        {--ignore-inactive : Entegrasyon is_active=false iken de zorla istegi gonder (opsiyonel)}';

    protected $description = 'Yemeksepeti Promotions async job bridge (start + status).';

    public function handle(): int
    {
        [$client, $settings] = $this->resolveClientAndSettings();
        if (! $client) {
            return self::FAILURE;
        }

        $chainId = (string) ($settings['chain_id'] ?? '');
        $vendorId = (string) ($settings['vendor_id'] ?? '');

        try {
            $jobId = trim((string) $this->option('job-id'));
            if ($jobId !== '') {
                if ($chainId === '') {
                    $this->error('chain_id gerekli.');
                    return self::FAILURE;
                }

                $res = $client->getPromotionJobStatus($chainId, $jobId);
                $this->line(json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return self::SUCCESS;
            }

            $file = trim((string) $this->option('payload-file'));
            if ($file === '') {
                $this->error('--payload-file gerekli (veya --job-id ile status check yapin).');
                return self::FAILURE;
            }

            if (! is_file($file)) {
                $this->error('Payload dosyasi bulunamadi: '.$file);
                return self::FAILURE;
            }

            $payload = json_decode((string) file_get_contents($file), true);
            if (! is_array($payload)) {
                $this->error('Payload gecerli JSON object/array olmali.');
                return self::FAILURE;
            }

            if ($chainId === '') {
                $this->error('chain_id gerekli.');
                return self::FAILURE;
            }
            if ($vendorId === '') {
                $this->error('vendor_id gerekli (payload icinde vendors yoksa).');
                return self::FAILURE;
            }

            // Dokümanda PUT /promotion => body {"vendors":[...], ...}
            // Kullanici payload icinde vendors alanini vermediyse default olarak settings vendor_id basariz.
            if (! array_key_exists('vendors', $payload) || ! is_array($payload['vendors'])) {
                $payload['vendors'] = [$vendorId];
            }

            $res = $client->updatePromotion($chainId, $payload);
            $this->info('Promotions async job tetiklendi.');
            $this->line(json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Promotions bridge hatasi: '.$e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * @return array{0: YemeksepetiPartnerApiClient|null, 1: array<string,mixed>}
     */
    private function resolveClientAndSettings(): array
    {
        $restaurantId = (int) $this->option('restaurant');
        if ($restaurantId <= 0) {
            $this->error('--restaurant zorunlu.');
            return [null, []];
        }

        $conn = IntegrationConnection::query()
            ->where('restaurant_id', $restaurantId)
            ->where('provider', 'yemeksepeti')
            ->when(! $this->option('ignore-inactive'), fn ($q) => $q->where('is_active', true))
            ->first();

        if (! $conn) {
            $this->error('Yemeksepeti entegrasyon baglantisi bulunamadi.');
            return [null, []];
        }

        $settings = is_array($conn->settings_json) ? $conn->settings_json : [];
        $credentials = $this->decryptCredentials($conn->credentials_encrypted);
        $baseUrl = $this->normalizeBaseUrl((string) ($this->option('base-url') ?: ($settings['api_base_url'] ?? 'https://yemeksepeti.partner.deliveryhero.io')));
        $clientId = (string) ($this->option('client-id') ?: ($credentials['client_id'] ?? ''));
        $clientSecret = (string) ($this->option('client-secret') ?: ($credentials['client_secret'] ?? ''));

        if ($clientId === '' || $clientSecret === '') {
            $this->error('client_id ve client_secret gerekli.');
            return [null, []];
        }

        return [new YemeksepetiPartnerApiClient($baseUrl, $clientId, $clientSecret), $settings];
    }

    /**
     * @return array<string,mixed>
     */
    private function decryptCredentials(?string $encrypted): array
    {
        if ($encrypted === null || $encrypted === '') {
            return [];
        }

        try {
            $json = Crypt::decryptString($encrypted);
            $decoded = json_decode($json, true);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function normalizeBaseUrl(string $baseUrl): string
    {
        $u = rtrim($baseUrl, '/');
        if ($u === 'https://api.partner.yemeksepeti.com') {
            return 'https://yemeksepeti.partner.deliveryhero.io';
        }
        return $u;
    }
}
