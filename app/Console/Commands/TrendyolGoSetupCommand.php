<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Integrations\Models\IntegrationConnection;
use App\Modules\Restaurants\Models\Restaurant;
use App\Services\Integrations\TrendyolGoMealWebhookApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

final class TrendyolGoSetupCommand extends Command
{
    protected $signature = 'kurye:tgo-setup
                            {--restaurant= : Restoran id (zorunlu)}
                            {--env=prod : prod|stage (TGO API ortamı)}
                            {--create-integrator : Integrator oluşturur (token üretir)}
                            {--refresh-token : Token yeniler}
                            {--add-seller : Seller ID ekler}
                            {--enable : Entegrasyonu enable eder}
                            {--test-order : Test order oluşturur}';

    protected $description = 'Trendyol Go (TGO) Yemek webhook entegrasyonu kurulum yardımcı komutu.';

    public function handle(): int
    {
        $rid = (int) ($this->option('restaurant') ?? 0);
        if ($rid <= 0) {
            $this->error('--restaurant zorunlu (ör. --restaurant=12).');
            return self::FAILURE;
        }

        $restaurant = Restaurant::query()->find($rid);
        if ($restaurant === null) {
            $this->error('Restoran bulunamadı.');
            return self::FAILURE;
        }

        $connection = IntegrationConnection::query()
            ->where('firm_id', $restaurant->firm_id)
            ->where('restaurant_id', $restaurant->id)
            ->where('provider', 'trendyol_yemek')
            ->first();

        if ($connection === null) {
            $this->error('Bu restoran için trendyol_yemek entegrasyon kaydı yok. Panelden entegrasyonu ekleyip kaydedin.');
            return self::FAILURE;
        }

        $settings = is_array($connection->settings_json) ? $connection->settings_json : [];
        $integratorName = (string) ($settings['integrator_name'] ?? '');
        $executorEmail = (string) ($settings['executor_user_email'] ?? '');

        if ($integratorName === '' || $executorEmail === '') {
            $this->error('integrator_name ve executor_user_email gerekli. Restaurant paneli > Entegrasyonlar > Trendyol Yemek kısmından girin.');
            return self::FAILURE;
        }

        $creds = $this->decryptCreds($connection->credentials_encrypted);
        $sellerId = (string) ($creds['seller_id'] ?? '');
        $apiKey = (string) ($creds['api_key'] ?? '');
        $apiSecret = (string) ($creds['api_secret'] ?? '');
        $bearerToken = (string) ($creds['access_token'] ?? '');

        if ($sellerId === '' || $apiKey === '' || $apiSecret === '') {
            $this->error('seller_id, api_key ve api_secret gerekli. Restaurant paneli > Entegrasyonlar > Trendyol Yemek kısmından girin.');
            return self::FAILURE;
        }

        $env = strtolower((string) $this->option('env'));
        $apiBaseUrl = $env === 'stage' ? 'https://stageapi.tgoapis.com' : 'https://api.tgoapis.com';
        $apiBaseUrl = is_string($settings['api_base_url'] ?? null) && $settings['api_base_url'] !== ''
            ? (string) $settings['api_base_url']
            : $apiBaseUrl;

        $client = new TrendyolGoMealWebhookApiClient(
            baseUrl: $apiBaseUrl,
            apiKey: $apiKey,
            apiSecret: $apiSecret,
        );

        $webhookToken = (string) ($settings['webhook_token'] ?? '');
        if ($webhookToken === '') {
            $webhookToken = Str::random(48);
            $settings['webhook_token'] = $webhookToken;
            $connection->settings_json = $settings;
            $connection->save();
        }

        $webhookBaseUrl = rtrim((string) config('app.url'), '/');
        $webhookPath = '/api/v1/integrations/trendyol_yemek/webhook';

        $this->line('Webhook endpoint: '.url($webhookPath));
        $this->line('X-Integration-Token: '.$webhookToken);
        $this->newLine();

        $didSomething = false;

        if ($this->option('create-integrator')) {
            $didSomething = true;
            $this->info('Integrator oluşturuluyor...');
            $res = $client->createIntegrator(
                integratorName: $integratorName,
                executorUserEmail: $executorEmail,
                webhookBaseUrl: $webhookBaseUrl,
                webhookDestinationPath: $webhookPath,
                integrationToken: $webhookToken,
                sellerId: $sellerId,
            );

            $token = (string) ($res['token'] ?? ($res['accessToken'] ?? ($res['access_token'] ?? '')));
            if ($token === '') {
                $this->warn('Create integrator yanıtında token alanı bulunamadı. Yanıtı kontrol edin.');
            } else {
                $creds['access_token'] = $token;
                $bearerToken = $token;
                $connection->credentials_encrypted = Crypt::encryptString((string) json_encode($creds, JSON_UNESCAPED_UNICODE));
                $connection->save();
                $this->info('Token kaydedildi (credentials_encrypted.access_token).');
            }
        }

        if ($this->option('refresh-token')) {
            $didSomething = true;
            if ($bearerToken === '') {
                $this->error('Önce integrator token gerekli (create-integrator veya panelden access_token).');
                return self::FAILURE;
            }
            $this->info('Token yenileniyor...');
            $res = $client->refreshIntegratorToken($integratorName, $executorEmail, $bearerToken);
            $token = (string) ($res['token'] ?? ($res['accessToken'] ?? ($res['access_token'] ?? '')));
            if ($token !== '') {
                $creds['access_token'] = $token;
                $bearerToken = $token;
                $connection->credentials_encrypted = Crypt::encryptString((string) json_encode($creds, JSON_UNESCAPED_UNICODE));
                $connection->save();
                $this->info('Yeni token kaydedildi.');
            } else {
                $this->warn('Token refresh yanıtında token alanı bulunamadı.');
            }
        }

        if ($this->option('add-seller')) {
            $didSomething = true;
            if ($bearerToken === '') {
                $this->error('Önce integrator token gerekli (create-integrator veya panelden access_token).');
                return self::FAILURE;
            }
            $this->info('Seller ekleniyor...');
            $client->addSeller($integratorName, $executorEmail, $bearerToken, $sellerId);
            $this->info('OK.');
        }

        if ($this->option('enable')) {
            $didSomething = true;
            if ($bearerToken === '') {
                $this->error('Önce integrator token gerekli (create-integrator veya panelden access_token).');
                return self::FAILURE;
            }
            $this->info('Integration enable ediliyor...');
            $client->enableIntegration($integratorName, $executorEmail, $bearerToken);
            $this->info('OK.');
        }

        if ($this->option('test-order')) {
            $didSomething = true;
            if ($bearerToken === '') {
                $this->error('Önce integrator token gerekli (create-integrator veya panelden access_token).');
                return self::FAILURE;
            }
            $this->info('Test order oluşturuluyor...');
            $res = $client->createTestOrder($integratorName, $executorEmail, $bearerToken, $sellerId);
            $this->line(json_encode($res, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->info('Eğer entegrasyon enabled ise, test order event’i webhook olarak uygulamaya düşmelidir.');
        }

        if (! $didSomething) {
            $this->warn('Bir aksiyon seçmediniz. Örnek: --create-integrator --add-seller --enable --test-order');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string,mixed>
     */
    private function decryptCreds(?string $encrypted): array
    {
        if (! is_string($encrypted) || $encrypted === '') {
            return [];
        }

        try {
            $decoded = json_decode(Crypt::decryptString($encrypted), true);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }
}

