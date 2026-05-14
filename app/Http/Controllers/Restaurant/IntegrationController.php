<?php

declare(strict_types=1);

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Modules\Integrations\Models\IntegrationConnection;
use App\Modules\Integrations\Models\IntegrationProductMap;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use App\Services\Integrations\TrendyolGoMealWebhookApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class IntegrationController extends Controller
{
    public function index(Request $request): View
    {
        $restaurant = $this->restaurantForUser();
        $providers = config('marketplace_integrations.providers', []);
        $connections = IntegrationConnection::query()
            ->where('restaurant_id', $restaurant->id)
            ->get()
            ->keyBy('provider');

        $products = Product::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $mapsByProvider = IntegrationProductMap::query()
            ->where('restaurant_id', $restaurant->id)
            ->with('product')
            ->orderBy('external_sku')
            ->get()
            ->groupBy('provider');

        $rawDuzenle = $request->query('duzenle');
        $editingProvider = is_string($rawDuzenle) && array_key_exists($rawDuzenle, $providers)
            ? $rawDuzenle
            : null;

        $configuredProviderKeys = self::configuredProviderKeys($providers, $connections, $mapsByProvider);

        return view('restaurant.integrations', [
            'title' => 'Pazar yeri API',
            'restaurant' => $restaurant,
            'providers' => $providers,
            'connections' => $connections,
            'products' => $products,
            'mapsByProvider' => $mapsByProvider,
            'editingProvider' => $editingProvider,
            'configuredProviderKeys' => $configuredProviderKeys,
        ]);
    }

    public function update(Request $request, string $provider): RedirectResponse
    {
        $restaurant = $this->restaurantForUser();
        if (! array_key_exists($provider, config('marketplace_integrations.providers', []))) {
            abort(404);
        }

        $connection = IntegrationConnection::query()->firstOrNew([
            'firm_id' => $restaurant->firm_id,
            'restaurant_id' => $restaurant->id,
            'provider' => $provider,
        ]);

        $request->validate([
            'order_status_webhook_url' => ['nullable', 'string', 'max:2000'],
            'webhook_secret' => ['nullable', 'string', 'max:256'],
            'webhook_secret_clear' => ['nullable', 'boolean'],
            'chain_id' => ['nullable', 'string', 'max:120'],
            'vendor_id' => ['nullable', 'string', 'max:120'],
            'api_base_url' => ['nullable', 'string', 'max:500'],
            'client_id' => ['nullable', 'string', 'max:190'],
            'client_secret' => ['nullable', 'string', 'max:400'],
            // Trendyol Go / TGO (meal) - webhook integrator + seller credentials
            'integrator_name' => ['nullable', 'string', 'max:80'],
            'executor_user_email' => ['nullable', 'string', 'max:190'],
            'seller_id' => ['nullable', 'string', 'max:40'],
            'api_key' => ['nullable', 'string', 'max:190'],
            'api_secret' => ['nullable', 'string', 'max:400'],
            'access_token' => ['nullable', 'string', 'max:2000'],
        ]);

        $settings = $connection->settings_json ?? [];
        if (! isset($settings['webhook_token']) || ! is_string($settings['webhook_token']) || $settings['webhook_token'] === '') {
            $settings['webhook_token'] = Str::random(48);
        }

        $hookUrl = trim((string) $request->input('order_status_webhook_url', ''));
        if ($hookUrl === '') {
            unset($settings['order_status_webhook_url']);
        } elseif (filter_var($hookUrl, FILTER_VALIDATE_URL) === false) {
            return redirect()->back()->withInput()->withErrors([
                'order_status_webhook_url' => 'Geçerli bir http(s) adresi girin.',
            ]);
        } else {
            $settings['order_status_webhook_url'] = $hookUrl;
        }

        if ($request->boolean('webhook_secret_clear')) {
            unset($settings['webhook_secret']);
        } elseif ($request->filled('webhook_secret')) {
            $settings['webhook_secret'] = trim((string) $request->input('webhook_secret'));
        }

        $integratorName = trim((string) $request->input('integrator_name', ''));
        if ($integratorName !== '') {
            $settings['integrator_name'] = $integratorName;
        }

        $executorEmail = trim((string) $request->input('executor_user_email', ''));
        if ($executorEmail !== '') {
            $settings['executor_user_email'] = $executorEmail;
        }

        $chainId = trim((string) $request->input('chain_id', ''));
        $vendorId = trim((string) $request->input('vendor_id', ''));
        $apiBaseUrl = trim((string) $request->input('api_base_url', ''));
        if ($chainId !== '') {
            $settings['chain_id'] = $chainId;
        }
        if ($vendorId !== '') {
            $settings['vendor_id'] = $vendorId;
        }
        if ($apiBaseUrl !== '') {
            $settings['api_base_url'] = rtrim($apiBaseUrl, '/');
        }

        $clientId = trim((string) $request->input('client_id', ''));
        $clientSecret = trim((string) $request->input('client_secret', ''));
        $sellerId = trim((string) $request->input('seller_id', ''));
        $apiKey = trim((string) $request->input('api_key', ''));
        $apiSecret = trim((string) $request->input('api_secret', ''));
        $accessToken = trim((string) $request->input('access_token', ''));
        // Users often paste "Bearer <token>" - we store the raw token.
        $accessToken = preg_replace('/^Bearer\\s+/i', '', $accessToken ?? '') ?? '';

        if ($clientId !== '' || $clientSecret !== '' || $sellerId !== '' || $apiKey !== '' || $apiSecret !== '' || $accessToken !== '') {
            $existing = [];
            if (is_string($connection->credentials_encrypted) && $connection->credentials_encrypted !== '') {
                try {
                    $decoded = json_decode(Crypt::decryptString($connection->credentials_encrypted), true);
                    $existing = is_array($decoded) ? $decoded : [];
                } catch (\Throwable) {
                    $existing = [];
                }
            }

            if ($clientId !== '') {
                $existing['client_id'] = $clientId;
            }
            if ($clientSecret !== '') {
                $existing['client_secret'] = $clientSecret;
            }
            if ($sellerId !== '') {
                $existing['seller_id'] = $sellerId;
            }
            if ($apiKey !== '') {
                $existing['api_key'] = $apiKey;
            }
            if ($apiSecret !== '') {
                $existing['api_secret'] = $apiSecret;
            }
            if ($accessToken !== '') {
                $existing['access_token'] = $accessToken;
            }

            $connection->credentials_encrypted = Crypt::encryptString((string) json_encode($existing, JSON_UNESCAPED_UNICODE));
        }

        $connection->fill([
            'settings_json' => $settings,
            'is_active' => $request->boolean('is_active'),
        ]);
        $connection->save();

        return redirect()->route('restaurant.integrations.index', ['duzenle' => $provider])
            ->with('status', 'Entegrasyon kaydedildi.');
    }

    public function regenerateToken(string $provider): RedirectResponse
    {
        $restaurant = $this->restaurantForUser();
        if (! array_key_exists($provider, config('marketplace_integrations.providers', []))) {
            abort(404);
        }

        $connection = IntegrationConnection::query()->firstOrNew([
            'firm_id' => $restaurant->firm_id,
            'restaurant_id' => $restaurant->id,
            'provider' => $provider,
        ]);

        $settings = $connection->settings_json ?? [];
        $settings['webhook_token'] = Str::random(48);
        $connection->settings_json = $settings;
        $connection->is_active = true;
        $connection->save();

        return redirect()->route('restaurant.integrations.index', ['duzenle' => $provider])
            ->with('status', 'Yeni webhook token üretildi. Harici sistemde mutlaka güncelleyin.');
    }

    public function tgoCreateIntegrator(Request $request): RedirectResponse
    {
        $restaurant = $this->restaurantForUser();
        $connection = IntegrationConnection::query()->where('restaurant_id', $restaurant->id)->where('provider', 'trendyol_yemek')->first();
        if ($connection === null) {
            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'Önce Trendyol Yemek entegrasyonunu ekleyip kaydedin.']);
        }

        $settings = is_array($connection->settings_json) ? $connection->settings_json : [];
        $integratorName = trim((string) ($settings['integrator_name'] ?? ''));
        $executorEmail = trim((string) ($settings['executor_user_email'] ?? ''));
        $baseUrl = trim((string) ($settings['api_base_url'] ?? 'https://api.tgoapis.com'));

        $creds = $this->decryptCredentials($connection->credentials_encrypted);
        $sellerId = trim((string) ($creds['seller_id'] ?? ''));
        $apiKey = trim((string) ($creds['api_key'] ?? ''));
        $apiSecret = trim((string) ($creds['api_secret'] ?? ''));

        if ($integratorName === '' || $executorEmail === '' || $sellerId === '' || $apiKey === '' || $apiSecret === '') {
            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'Integrator name / executor e-mail / seller id / api key / api secret alanlarını doldurun.']);
        }

        // Ensure webhook token exists so TGO can send it back in events.
        if (! isset($settings['webhook_token']) || ! is_string($settings['webhook_token']) || $settings['webhook_token'] === '') {
            $settings['webhook_token'] = Str::random(48);
            $connection->settings_json = $settings;
            $connection->save();
        }

        $webhookToken = (string) $settings['webhook_token'];
        $webhookBaseUrl = rtrim((string) config('app.url'), '/');
        $looksPublic = $webhookBaseUrl !== '' && !str_contains($webhookBaseUrl, 'localhost') && !str_contains($webhookBaseUrl, '127.0.0.1');
        $isHttps = str_starts_with($webhookBaseUrl, 'https://');
        if (!$looksPublic || !$isHttps) {
            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors([
                    'trendyol_yemek' => 'TGO webhook için APP_URL dışarıdan erişilebilir ve https olmalı. Mevcut: '.$webhookBaseUrl,
                ]);
        }
        $webhookPath = '/api/v1/integrations/trendyol_yemek/webhook';

        try {
            $client = new TrendyolGoMealWebhookApiClient($baseUrl, $apiKey, $apiSecret);
            $res = $client->createIntegrator(
                integratorName: $integratorName,
                executorUserEmail: $executorEmail,
                webhookBaseUrl: $webhookBaseUrl,
                webhookDestinationPath: $webhookPath,
                integrationToken: $webhookToken,
                sellerId: $sellerId,
            );
        } catch (\Throwable $e) {
            $this->touchTgoMeta($connection, [
                'last_action' => 'create_integrator',
                'last_error_at' => now()->toISOString(),
                'last_error_message' => $e->getMessage(),
            ]);
            Log::warning('tgo_create_integrator_failed', [
                'restaurant_id' => $restaurant->id,
                'firm_id' => $restaurant->firm_id,
                'integrator_name' => $integratorName,
                'base_url' => $baseUrl,
                'exception' => $e->getMessage(),
            ]);

            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'TGO Create Integrator başarısız: '.$e->getMessage()]);
        }

        $token = trim((string) ($res['token'] ?? ($res['accessToken'] ?? ($res['access_token'] ?? ''))));
        if ($token !== '') {
            $creds['access_token'] = $token;
            $connection->credentials_encrypted = Crypt::encryptString((string) json_encode($creds, JSON_UNESCAPED_UNICODE));
            $connection->save();
        }

        $this->touchTgoMeta($connection, [
            'last_action' => 'create_integrator',
            'last_ok_at' => now()->toISOString(),
            'last_error_message' => null,
        ]);

        return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
            ->with('status', $token !== '' ? 'Integrator oluşturuldu ve token kaydedildi.' : 'Integrator oluşturuldu (token yanıtını kontrol edin).');
    }

    public function tgoRefreshToken(Request $request): RedirectResponse
    {
        $restaurant = $this->restaurantForUser();
        $connection = IntegrationConnection::query()->where('restaurant_id', $restaurant->id)->where('provider', 'trendyol_yemek')->first();
        if ($connection === null) {
            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'Önce Trendyol Yemek entegrasyonunu ekleyip kaydedin.']);
        }

        $settings = is_array($connection->settings_json) ? $connection->settings_json : [];
        $integratorName = trim((string) ($settings['integrator_name'] ?? ''));
        $executorEmail = trim((string) ($settings['executor_user_email'] ?? ''));
        $baseUrl = trim((string) ($settings['api_base_url'] ?? 'https://api.tgoapis.com'));

        $creds = $this->decryptCredentials($connection->credentials_encrypted);
        $apiKey = trim((string) ($creds['api_key'] ?? ''));
        $apiSecret = trim((string) ($creds['api_secret'] ?? ''));
        $bearer = trim((string) ($creds['access_token'] ?? ''));

        if ($integratorName === '' || $executorEmail === '' || $apiKey === '' || $apiSecret === '' || $bearer === '') {
            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'Token yenileme için integrator name / executor e-mail / api key-secret / access token gerekli.']);
        }

        try {
            $client = new TrendyolGoMealWebhookApiClient($baseUrl, $apiKey, $apiSecret);
            $res = $client->refreshIntegratorToken($integratorName, $executorEmail, $bearer);
        } catch (\Throwable $e) {
            $this->touchTgoMeta($connection, [
                'last_action' => 'refresh_token',
                'last_error_at' => now()->toISOString(),
                'last_error_message' => $e->getMessage(),
            ]);
            Log::warning('tgo_refresh_token_failed', [
                'restaurant_id' => $restaurant->id,
                'firm_id' => $restaurant->firm_id,
                'integrator_name' => $integratorName,
                'base_url' => $baseUrl,
                'exception' => $e->getMessage(),
            ]);

            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'TGO Refresh Token başarısız: '.$e->getMessage()]);
        }

        $token = trim((string) ($res['token'] ?? ($res['accessToken'] ?? ($res['access_token'] ?? ''))));
        if ($token !== '') {
            $creds['access_token'] = $token;
            $connection->credentials_encrypted = Crypt::encryptString((string) json_encode($creds, JSON_UNESCAPED_UNICODE));
            $connection->save();
        }

        $this->touchTgoMeta($connection, [
            'last_action' => 'refresh_token',
            'last_ok_at' => now()->toISOString(),
            'last_error_message' => null,
        ]);

        return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
            ->with('status', $token !== '' ? 'Token yenilendi ve kaydedildi.' : 'Token yenilendi (yanıtı kontrol edin).');
    }

    public function tgoAddSeller(Request $request): RedirectResponse
    {
        $restaurant = $this->restaurantForUser();
        $connection = IntegrationConnection::query()->where('restaurant_id', $restaurant->id)->where('provider', 'trendyol_yemek')->first();
        if ($connection === null) {
            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'Önce Trendyol Yemek entegrasyonunu ekleyip kaydedin.']);
        }

        $settings = is_array($connection->settings_json) ? $connection->settings_json : [];
        $integratorName = trim((string) ($settings['integrator_name'] ?? ''));
        $executorEmail = trim((string) ($settings['executor_user_email'] ?? ''));
        $baseUrl = trim((string) ($settings['api_base_url'] ?? 'https://api.tgoapis.com'));

        $creds = $this->decryptCredentials($connection->credentials_encrypted);
        $sellerId = trim((string) ($creds['seller_id'] ?? ''));
        $apiKey = trim((string) ($creds['api_key'] ?? ''));
        $apiSecret = trim((string) ($creds['api_secret'] ?? ''));
        $bearer = trim((string) ($creds['access_token'] ?? ''));

        if ($integratorName === '' || $executorEmail === '' || $sellerId === '' || $apiKey === '' || $apiSecret === '' || $bearer === '') {
            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'Seller eklemek için integrator name / executor e-mail / seller id / api key-secret / access token gerekli.']);
        }

        try {
            $client = new TrendyolGoMealWebhookApiClient($baseUrl, $apiKey, $apiSecret);
            $client->addSeller($integratorName, $executorEmail, $bearer, $sellerId);
        } catch (\Throwable $e) {
            $this->touchTgoMeta($connection, [
                'last_action' => 'add_seller',
                'last_error_at' => now()->toISOString(),
                'last_error_message' => $e->getMessage(),
            ]);
            Log::warning('tgo_add_seller_failed', [
                'restaurant_id' => $restaurant->id,
                'firm_id' => $restaurant->firm_id,
                'integrator_name' => $integratorName,
                'seller_id' => $sellerId,
                'base_url' => $baseUrl,
                'exception' => $e->getMessage(),
            ]);

            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'TGO Add Seller başarısız: '.$e->getMessage()]);
        }

        $this->touchTgoMeta($connection, [
            'last_action' => 'add_seller',
            'last_ok_at' => now()->toISOString(),
            'last_error_message' => null,
        ]);

        return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
            ->with('status', 'Seller eklendi.');
    }

    public function tgoEnable(Request $request): RedirectResponse
    {
        $restaurant = $this->restaurantForUser();
        $connection = IntegrationConnection::query()->where('restaurant_id', $restaurant->id)->where('provider', 'trendyol_yemek')->first();
        if ($connection === null) {
            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'Önce Trendyol Yemek entegrasyonunu ekleyip kaydedin.']);
        }

        $settings = is_array($connection->settings_json) ? $connection->settings_json : [];
        $integratorName = trim((string) ($settings['integrator_name'] ?? ''));
        $executorEmail = trim((string) ($settings['executor_user_email'] ?? ''));
        $baseUrl = trim((string) ($settings['api_base_url'] ?? 'https://api.tgoapis.com'));

        $creds = $this->decryptCredentials($connection->credentials_encrypted);
        $apiKey = trim((string) ($creds['api_key'] ?? ''));
        $apiSecret = trim((string) ($creds['api_secret'] ?? ''));
        $bearer = trim((string) ($creds['access_token'] ?? ''));

        if ($integratorName === '' || $executorEmail === '' || $apiKey === '' || $apiSecret === '' || $bearer === '') {
            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'Enable için integrator name / executor e-mail / api key-secret / access token gerekli.']);
        }

        try {
            $client = new TrendyolGoMealWebhookApiClient($baseUrl, $apiKey, $apiSecret);
            $client->enableIntegration($integratorName, $executorEmail, $bearer);
        } catch (\Throwable $e) {
            $this->touchTgoMeta($connection, [
                'last_action' => 'enable',
                'last_error_at' => now()->toISOString(),
                'last_error_message' => $e->getMessage(),
            ]);
            Log::warning('tgo_enable_failed', [
                'restaurant_id' => $restaurant->id,
                'firm_id' => $restaurant->firm_id,
                'integrator_name' => $integratorName,
                'base_url' => $baseUrl,
                'exception' => $e->getMessage(),
            ]);

            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'TGO Enable başarısız: '.$e->getMessage()]);
        }

        $this->touchTgoMeta($connection, [
            'last_action' => 'enable',
            'last_ok_at' => now()->toISOString(),
            'last_error_message' => null,
        ]);

        return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
            ->with('status', 'Entegrasyon enable edildi.');
    }

    public function tgoTestOrder(Request $request): RedirectResponse
    {
        $restaurant = $this->restaurantForUser();
        $connection = IntegrationConnection::query()->where('restaurant_id', $restaurant->id)->where('provider', 'trendyol_yemek')->first();
        if ($connection === null) {
            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'Önce Trendyol Yemek entegrasyonunu ekleyip kaydedin.']);
        }

        $settings = is_array($connection->settings_json) ? $connection->settings_json : [];
        $integratorName = trim((string) ($settings['integrator_name'] ?? ''));
        $executorEmail = trim((string) ($settings['executor_user_email'] ?? ''));
        $baseUrl = trim((string) ($settings['api_base_url'] ?? 'https://api.tgoapis.com'));

        $creds = $this->decryptCredentials($connection->credentials_encrypted);
        $sellerId = trim((string) ($creds['seller_id'] ?? ''));
        $apiKey = trim((string) ($creds['api_key'] ?? ''));
        $apiSecret = trim((string) ($creds['api_secret'] ?? ''));
        $bearer = trim((string) ($creds['access_token'] ?? ''));

        if ($integratorName === '' || $executorEmail === '' || $sellerId === '' || $apiKey === '' || $apiSecret === '' || $bearer === '') {
            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'Test order için integrator name / executor e-mail / seller id / api key-secret / access token gerekli.']);
        }

        try {
            $client = new TrendyolGoMealWebhookApiClient($baseUrl, $apiKey, $apiSecret);
            $client->createTestOrder($integratorName, $executorEmail, $bearer, $sellerId);
        } catch (\Throwable $e) {
            $this->touchTgoMeta($connection, [
                'last_action' => 'test_order',
                'last_error_at' => now()->toISOString(),
                'last_error_message' => $e->getMessage(),
            ]);
            Log::warning('tgo_test_order_failed', [
                'restaurant_id' => $restaurant->id,
                'firm_id' => $restaurant->firm_id,
                'integrator_name' => $integratorName,
                'seller_id' => $sellerId,
                'base_url' => $baseUrl,
                'exception' => $e->getMessage(),
            ]);

            $details = $e->getMessage();
            if ($e instanceof RequestException && $e->response) {
                $body = (string) $e->response->body();
                $details = "HTTP {$e->response->status()}: {$body}";
            }

            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors([
                    'trendyol_yemek' => 'TGO Test Order başarısız: '.$details.' (Base URL: '.$baseUrl.')',
                ]);
        }

        $this->touchTgoMeta($connection, [
            'last_action' => 'test_order',
            'last_ok_at' => now()->toISOString(),
            'last_error_message' => null,
        ]);

        return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
            ->with('status', 'Test order tetiklendi. Eğer enable ise sipariş birkaç saniye içinde düşmelidir.');
    }

    public function tgoAutoConnect(Request $request): RedirectResponse
    {
        $restaurant = $this->restaurantForUser();
        $connection = IntegrationConnection::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('provider', 'trendyol_yemek')
            ->first();

        if ($connection === null) {
            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'Önce Trendyol Yemek entegrasyonunu ekleyip kaydedin.']);
        }

        $settings = is_array($connection->settings_json) ? $connection->settings_json : [];
        $integratorName = trim((string) ($settings['integrator_name'] ?? ''));
        $executorEmail = trim((string) ($settings['executor_user_email'] ?? ''));
        $baseUrl = trim((string) ($settings['api_base_url'] ?? 'https://api.tgoapis.com'));

        $creds = $this->decryptCredentials($connection->credentials_encrypted);
        $sellerId = trim((string) ($creds['seller_id'] ?? ''));
        $apiKey = trim((string) ($creds['api_key'] ?? ''));
        $apiSecret = trim((string) ($creds['api_secret'] ?? ''));

        if ($integratorName === '' || $executorEmail === '' || $sellerId === '' || $apiKey === '' || $apiSecret === '') {
            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'Otomatik bağlanmak için Integrator name / executor e-mail / seller id / api key / api secret gerekli.']);
        }

        if (! isset($settings['webhook_token']) || ! is_string($settings['webhook_token']) || $settings['webhook_token'] === '') {
            $settings['webhook_token'] = Str::random(48);
            $connection->settings_json = $settings;
            $connection->save();
        }

        $webhookToken = (string) $settings['webhook_token'];
        $webhookBaseUrl = rtrim((string) config('app.url'), '/');
        $webhookPath = '/api/v1/integrations/trendyol_yemek/webhook';

        $step = '';
        try {
            $client = new TrendyolGoMealWebhookApiClient($baseUrl, $apiKey, $apiSecret);

            $step = 'Create Integrator';
            $res = $client->createIntegrator(
                integratorName: $integratorName,
                executorUserEmail: $executorEmail,
                webhookBaseUrl: $webhookBaseUrl,
                webhookDestinationPath: $webhookPath,
                integrationToken: $webhookToken,
                sellerId: $sellerId,
            );

            $token = trim((string) ($res['token'] ?? ($res['accessToken'] ?? ($res['access_token'] ?? ''))));
            $token = preg_replace('/^Bearer\\s+/i', '', $token ?? '') ?? '';
            if ($token !== '') {
                $creds['access_token'] = $token;
                $connection->credentials_encrypted = Crypt::encryptString((string) json_encode($creds, JSON_UNESCAPED_UNICODE));
                $connection->save();
            }

            // Optional step: some accounts still require explicit seller add.
            $step = 'Add Seller';
            try {
                $client->addSeller($integratorName, $executorEmail, (string) ($creds['access_token'] ?? ''), $sellerId);
            } catch (\Throwable $e) {
                // Ignore "already added" style conflicts; surface anything else.
                if ($e instanceof RequestException && $e->response && in_array($e->response->status(), [409, 422], true)) {
                    // ok-ish
                } else {
                    throw $e;
                }
            }

            $step = 'Enable';
            $client->enableIntegration($integratorName, $executorEmail, (string) ($creds['access_token'] ?? ''));

            $step = 'Test Order';
            $client->createTestOrder($integratorName, $executorEmail, (string) ($creds['access_token'] ?? ''), $sellerId);
        } catch (\Throwable $e) {
            $details = $e->getMessage();
            if ($e instanceof RequestException && $e->response) {
                $details = "HTTP {$e->response->status()}: ".(string) $e->response->body();
            }

            $this->touchTgoMeta($connection, [
                'last_action' => 'auto_connect',
                'last_error_at' => now()->toISOString(),
                'last_error_message' => $step.': '.$details,
            ]);

            return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
                ->withErrors(['trendyol_yemek' => 'TGO Otomatik Bağla başarısız ('.$step.'): '.$details.' (Base URL: '.$baseUrl.')']);
        }

        $this->touchTgoMeta($connection, [
            'last_action' => 'auto_connect',
            'last_ok_at' => now()->toISOString(),
            'last_error_message' => null,
        ]);

        return redirect()->route('restaurant.integrations.index', ['duzenle' => 'trendyol_yemek'])
            ->with('status', 'TGO otomatik bağlandı: integrator oluşturuldu, enable edildi ve test order tetiklendi.');
    }

    public function storeProductMap(Request $request, string $provider): RedirectResponse
    {
        $restaurant = $this->restaurantForUser();
        if (! array_key_exists($provider, config('marketplace_integrations.providers', []))) {
            abort(404);
        }

        $data = $request->validate([
            'external_sku' => ['required', 'string', 'max:190'],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(
                    fn ($q) => $q->where('restaurant_id', $restaurant->id)->where('status', 'active')
                ),
            ],
        ]);

        IntegrationProductMap::query()->updateOrCreate(
            [
                'restaurant_id' => $restaurant->id,
                'provider' => $provider,
                'external_sku' => trim($data['external_sku']),
            ],
            ['product_id' => $data['product_id']],
        );

        return redirect()->route('restaurant.integrations.index', ['duzenle' => $provider])
            ->with('status', 'Ürün eşlemesi kaydedildi.');
    }

    public function importProductMaps(Request $request, string $provider): RedirectResponse
    {
        $restaurant = $this->restaurantForUser();
        if (! array_key_exists($provider, config('marketplace_integrations.providers', []))) {
            abort(404);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:2048'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'json'], true)) {
            return redirect()->route('restaurant.integrations.index', ['duzenle' => $provider])
                ->withErrors(['file' => 'Sadece CSV veya JSON dosyası yükleyin.']);
        }

        $rows = $this->readImportRowsFromUpload($file, $ext);
        if ($rows === []) {
            return redirect()->route('restaurant.integrations.index', ['duzenle' => $provider])
                ->withErrors(['file' => 'Dosyada import edilebilir satır bulunamadı.']);
        }

        $ok = 0;
        $fail = 0;
        $failMessages = [];
        foreach ($rows as $idx => $row) {
            $line = $idx + 1;
            $externalSku = trim((string) ($row['external_sku'] ?? ($row['sku'] ?? '')));
            $productId = (int) ($row['product_id'] ?? 0);

            if ($externalSku === '' || $productId < 1) {
                $fail++;
                $failMessages[] = "Satır {$line}: external_sku/product_id geçersiz.";
                continue;
            }

            $exists = Product::query()
                ->where('restaurant_id', $restaurant->id)
                ->where('status', 'active')
                ->whereKey($productId)
                ->exists();

            if (! $exists) {
                $fail++;
                $failMessages[] = "Satır {$line}: product_id={$productId} bu restoranda aktif değil.";
                continue;
            }

            IntegrationProductMap::query()->updateOrCreate(
                [
                    'restaurant_id' => $restaurant->id,
                    'provider' => $provider,
                    'external_sku' => $externalSku,
                ],
                ['product_id' => $productId]
            );
            $ok++;
        }

        if ($fail > 0) {
            $preview = implode(' ', array_slice($failMessages, 0, 6));
            if (count($failMessages) > 6) {
                $preview .= ' …';
            }

            return redirect()->route('restaurant.integrations.index', ['duzenle' => $provider])
                ->withErrors(['file' => "Import bitti. Başarılı: {$ok}, Hatalı/atlanmış: {$fail}. {$preview}"])
                ->with('status', "Import bitti. Başarılı: {$ok}, Hatalı/atlanmış: {$fail}.");
        }

        return redirect()->route('restaurant.integrations.index', ['duzenle' => $provider])
            ->with('status', "Import tamamlandı. Başarılı: {$ok}.");
    }

    public function downloadProductMapTemplate(Request $request, string $provider): Response
    {
        $restaurant = $this->restaurantForUser();
        if (! array_key_exists($provider, config('marketplace_integrations.providers', []))) {
            abort(404);
        }

        $rows = Product::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'price']);

        $filename = "product-map-template-{$restaurant->id}-{$provider}.csv";

        $lines = [];
        $lines[] = "\xEF\xBB\xBFexternal_sku,product_id,product_name,price";
        foreach ($rows as $p) {
            $externalSku = '';
            $productId = (int) $p->id;
            $name = str_replace('"', '""', (string) $p->name);
            $price = (string) $p->price;
            $lines[] = "\"{$externalSku}\",\"{$productId}\",\"{$name}\",\"{$price}\"";
        }

        $csv = implode("\n", $lines)."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function destroyProductMap(string $provider, IntegrationProductMap $productMap): RedirectResponse
    {
        $restaurant = $this->restaurantForUser();
        if ($productMap->restaurant_id !== $restaurant->id || $productMap->provider !== $provider) {
            abort(403);
        }

        $productMap->delete();

        return redirect()->route('restaurant.integrations.index', ['duzenle' => $provider])
            ->with('status', 'Ürün eşlemesi silindi.');
    }

    /**
     * @param  array<string, mixed>  $providers
     */
    private static function configuredProviderKeys(
        array $providers,
        Collection $connections,
        Collection $mapsByProvider,
    ): Collection {
        return collect(array_keys($providers))
            ->filter(function (string $key) use ($connections, $mapsByProvider): bool {
                if ($connections->has($key)) {
                    return true;
                }

                return ($mapsByProvider->get($key) ?? collect())->isNotEmpty();
            })
            ->values();
    }

    private function restaurantForUser(): Restaurant
    {
        return Restaurant::query()->findOrFail(Auth::user()->restaurant_id);
    }

    /**
     * @return array<string,mixed>
     */
    private function decryptCredentials(?string $encrypted): array
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

    /**
     * @return array<int, array<string,mixed>>
     */
    private function readImportRowsFromUpload(UploadedFile $file, string $ext): array
    {
        if ($ext === 'json') {
            $raw = $file->get();
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                return [];
            }
            return array_values(array_filter($decoded, fn ($x) => is_array($x)));
        }

        $path = $file->getRealPath();
        if (! is_string($path) || $path === '' || ! is_file($path)) {
            return [];
        }

        $h = fopen($path, 'rb');
        if ($h === false) {
            return [];
        }
        $header = fgetcsv($h);
        if (! is_array($header)) {
            fclose($h);
            return [];
        }
        $header = array_map(function ($x): string {
            $k = strtolower(trim((string) $x));
            return ltrim($k, "\xEF\xBB\xBF");
        }, $header);

        $out = [];
        while (($row = fgetcsv($h)) !== false) {
            if (! is_array($row)) {
                continue;
            }
            $assoc = [];
            foreach ($header as $i => $k) {
                if ($k === '') {
                    continue;
                }
                $assoc[$k] = $row[$i] ?? null;
            }
            if ($assoc !== []) {
                $out[] = $assoc;
            }
        }
        fclose($h);

        return $out;
    }

    /**
     * @param array<string,mixed> $patch
     */
    private function touchTgoMeta(IntegrationConnection $connection, array $patch): void
    {
        $settings = is_array($connection->settings_json) ? $connection->settings_json : [];
        $meta = is_array($settings['tgo_meta'] ?? null) ? $settings['tgo_meta'] : [];
        $settings['tgo_meta'] = array_merge($meta, $patch);
        $connection->settings_json = $settings;
        $connection->save();
    }
}
