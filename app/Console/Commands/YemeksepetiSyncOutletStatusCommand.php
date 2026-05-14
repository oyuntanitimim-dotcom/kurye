<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Integrations\Models\IntegrationConnection;
use App\Services\Integrations\YemeksepetiPartnerApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class YemeksepetiSyncOutletStatusCommand extends Command
{
    protected $signature = 'kurye:yemeksepeti-outlet-status
        {--restaurant= : Restaurant ID}
        {--status=OPEN : OPEN|CLOSED_TODAY|CLOSED_UNTIL|CHECKIN (backward compatible: CLOSED -> CLOSED_UNTIL)}
        {--closed-reason= : Closed reason text}
        {--closed-until= : ISO8601 timestamp}
        {--check : Sadece anlik status cek}
        {--base-url= : API base URL (default https://yemeksepeti.partner.deliveryhero.io)}
        {--chain-id= : Chain ID override}
        {--vendor-id= : Vendor ID override}
        {--client-id= : Client ID override}
        {--client-secret= : Client Secret override}
        {--ignore-inactive : Entegrasyon is_active=false iken de zorla istegi gonder (opsiyonel)}';

    protected $description = 'Yemeksepeti outlet status senkronizasyonu yapar.';

    public function handle(): int
    {
        $restaurantId = (int) $this->option('restaurant');
        if ($restaurantId <= 0) {
            $this->error('--restaurant zorunlu.');
            return self::FAILURE;
        }

        $conn = IntegrationConnection::query()
            ->where('restaurant_id', $restaurantId)
            ->where('provider', 'yemeksepeti')
            ->when(! $this->option('ignore-inactive'), fn ($q) => $q->where('is_active', true))
            ->first();

        if (! $conn) {
            $this->error('Yemeksepeti entegrasyon baglantisi bulunamadi.');
            return self::FAILURE;
        }

        $settings = is_array($conn->settings_json) ? $conn->settings_json : [];
        $credentials = $this->decryptCredentials($conn->credentials_encrypted);

        $baseUrl = $this->normalizeBaseUrl((string) ($this->option('base-url') ?: ($settings['api_base_url'] ?? 'https://yemeksepeti.partner.deliveryhero.io')));
        $chainId = (string) ($this->option('chain-id') ?: ($settings['chain_id'] ?? ''));
        $vendorId = (string) ($this->option('vendor-id') ?: ($settings['vendor_id'] ?? ''));
        $clientId = (string) ($this->option('client-id') ?: ($credentials['client_id'] ?? ''));
        $clientSecret = (string) ($this->option('client-secret') ?: ($credentials['client_secret'] ?? ''));

        if ($chainId === '' || $vendorId === '' || $clientId === '' || $clientSecret === '') {
            $this->error('chain_id, vendor_id, client_id, client_secret degerleri gerekli.');
            return self::FAILURE;
        }

        $client = new YemeksepetiPartnerApiClient($baseUrl, $clientId, $clientSecret);

        try {
            if ($this->option('check')) {
                $status = $client->getOutletStatus($chainId, $vendorId);
                $this->line(json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return self::SUCCESS;
            }

            $targetStatus = strtoupper((string) $this->option('status'));
            if ($targetStatus === 'CLOSED') {
                $targetStatus = 'CLOSED_UNTIL';
            }

            $allowed = ['OPEN', 'CLOSED_TODAY', 'CLOSED_UNTIL', 'CHECKIN'];
            if (! in_array($targetStatus, $allowed, true)) {
                $this->error('--status OPEN|CLOSED_TODAY|CLOSED_UNTIL|CHECKIN olmali (CLOSED da desteklenir).');
                return self::FAILURE;
            }

            $closedUntil = $this->option('closed-until') ? (string) $this->option('closed-until') : null;
            $closedReason = $this->option('closed-reason') ? (string) $this->option('closed-reason') : null;

            if ($targetStatus === 'CLOSED_UNTIL' && ($closedUntil === null || $closedUntil === '')) {
                $this->error('--closed-until gerekli (CLOSED_UNTIL icin).');
                return self::FAILURE;
            }

            if ($targetStatus === 'OPEN') {
                // OPEN iken reason/until yollamayalim.
                $closedUntil = null;
                $closedReason = null;
            }

            if ($targetStatus !== 'CLOSED_UNTIL') {
                $closedUntil = null;
            }

            $res = $client->updateOutletStatus(
                $chainId,
                $vendorId,
                $targetStatus,
                $closedReason,
                $closedUntil
            );

            $this->info('Outlet status gonderildi.');
            $this->line(json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Yemeksepeti istegi basarisiz: '.$e->getMessage());
            return self::FAILURE;
        }
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
