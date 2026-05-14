@php
    $conn = $connections->get($providerKey);
    $token = $conn?->settings_json['webhook_token'] ?? null;
    $hasWebhookSecret = !empty($conn?->settings_json['webhook_secret']);
    $webhookUrl = url('/api/v1/integrations/'.$providerKey.'/webhook');
    $isActive = $conn?->is_active ?? false;
    $maps = $mapsByProvider->get($providerKey, collect());

    $credentials = [];
    if (is_string($conn?->credentials_encrypted) && $conn->credentials_encrypted !== '') {
        try {
            $credentials = json_decode(\Illuminate\Support\Facades\Crypt::decryptString($conn->credentials_encrypted), true);
            $credentials = is_array($credentials) ? $credentials : [];
        } catch (\Throwable) {
            $credentials = [];
        }
    }

    $hasAccessToken = !empty($credentials['access_token']);
    $hasApiKeySecret = !empty($credentials['api_key']) && !empty($credentials['api_secret']);
    $hasSellerId = !empty($credentials['seller_id']);
    $hasIntegratorMeta = !empty($conn?->settings_json['integrator_name']) && !empty($conn?->settings_json['executor_user_email']);

    $appUrl = rtrim((string) config('app.url'), '/');
    $appUrlLooksPublic = $appUrl !== '' && !str_contains($appUrl, 'localhost') && !str_contains($appUrl, '127.0.0.1');
    $appUrlIsHttps = str_starts_with($appUrl, 'https://');

    $tgoMeta = is_array($conn?->settings_json['tgo_meta'] ?? null) ? ($conn->settings_json['tgo_meta'] ?? []) : [];
    $lastWebhookAt = $conn?->settings_json['last_webhook_received_at'] ?? null;
    $whStats = is_array($conn?->settings_json['webhook_stats'] ?? null) ? ($conn->settings_json['webhook_stats'] ?? []) : [];
    $whTotal = (int) ($whStats['total'] ?? 0);
    $whByDay = is_array($whStats['by_day'] ?? null) ? $whStats['by_day'] : [];
    $whToday = (int) ($whByDay[\Illuminate\Support\Carbon::now()->format('Y-m-d')] ?? 0);
    $whLastEventType = (string) ($whStats['last_event_type'] ?? '');
    $whLastExternalId = (string) ($whStats['last_external_order_id'] ?? '');
    $whLastStatusUpdate = (string) ($whStats['last_status_update'] ?? '');
@endphp
<section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
        <div class="min-w-0 flex-1">
            <h2 class="text-lg font-semibold">{{ $meta['label'] }}</h2>
            <p class="text-sm text-slate-600 mt-1">{{ $meta['description'] }}</p>
            <div class="mt-2 flex flex-wrap gap-3 text-sm">
                @if(!empty($meta['documentation_url']))
                    <a href="{{ $meta['documentation_url'] }}" target="_blank" rel="noopener noreferrer" class="text-amber-700 hover:underline">Resmi dokümantasyon</a>
                @endif
                @if(!empty($meta['documentation_url_alt']))
                    <a href="{{ $meta['documentation_url_alt'] }}" target="_blank" rel="noopener noreferrer" class="text-amber-700 hover:underline">Ek kaynak</a>
                @endif
            </div>
        </div>
        <a href="{{ route('restaurant.integrations.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
            Listeye dön
        </a>
    </div>

    <div class="space-y-3 text-sm">
        <div>
            <span class="text-slate-500 block mb-1">Webhook URL</span>
            <code class="block break-all rounded bg-slate-100 px-3 py-2 text-xs">{{ $webhookUrl }}</code>
        </div>
        @if($token)
            <div>
                <span class="text-slate-500 block mb-1">X-Integration-Token</span>
                <code class="block break-all rounded bg-slate-100 px-3 py-2 text-xs">{{ $token }}</code>
            </div>
        @else
            <p class="text-slate-500">Henüz token yok — aşağıdan etkinleştirerek oluşturun.</p>
        @endif
        @if($providerKey !== 'trendyol_yemek')
            <p class="text-xs text-slate-500 mt-2">
                İsteğe bağlı imza: panelde gizli anahtar tanımlıysa harici sistem aynı ham JSON gövdesi için
                <code class="bg-slate-100 px-1 rounded">X-Integration-Signature: sha256=</code> + HMAC-SHA256 (hex) göndermelidir.
                @if($hasWebhookSecret)
                    <span class="text-amber-800 font-medium">Bu bağlantıda imza zorunlu.</span>
                @endif
            </p>
        @endif
    </div>

    <div class="mt-6 space-y-4">
        <form method="post" action="{{ route('restaurant.integrations.update', $providerKey, false) }}" class="space-y-4">
            @csrf
            @method('PUT')

            @if($providerKey !== 'trendyol_yemek')
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Durum geri bildirimi URL’si (isteğe bağlı)</label>
                    <input type="url" name="order_status_webhook_url" value="{{ old('order_status_webhook_url', $conn?->settings_json['order_status_webhook_url'] ?? '') }}" placeholder="https://sizin-sunucunuz.com/yemeksepeti/durum" class="w-full max-w-xl rounded border border-slate-300 px-3 py-2 text-sm">
                    <p class="text-xs text-slate-500 mt-1">Sipariş <strong>hazır</strong> veya <strong>iptal</strong> olduğunda bu adrese JSON POST edilir; başlıkta aynı <code class="bg-slate-100 px-1 rounded">X-Integration-Token</code> gider.</p>
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Webhook gizli anahtarı (isteğe bağlı, imza doğrulama)</label>
                    @if($hasWebhookSecret)
                        <p class="text-xs text-slate-500 mb-1">Kayıtlı gizli anahtar güvenlik nedeniyle gösterilmez. Değiştirmek için yeni değer girin veya aşağıdan kaldırın.</p>
                    @endif
                    <input type="password" name="webhook_secret" value="" autocomplete="new-password" placeholder="Boş bırakırsanız mevcut anahtar korunur" class="w-full max-w-xl rounded border border-slate-300 px-3 py-2 text-sm font-mono">
                    <label class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="webhook_secret_clear" value="1" class="rounded border-slate-300" @checked(old('webhook_secret_clear'))>
                        <span>Gizli anahtarı kaldır (imza zorunluluğu kalkar)</span>
                    </label>
                </div>
            @endif

            <div class="grid gap-3 md:grid-cols-2">
                @if($providerKey === 'yemeksepeti')
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Yemeksepeti API Base URL</label>
                        <input type="url" name="api_base_url" value="{{ old('api_base_url', $conn?->settings_json['api_base_url'] ?? 'https://yemeksepeti.partner.deliveryhero.io') }}" placeholder="https://yemeksepeti.partner.deliveryhero.io" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Chain ID</label>
                        <input type="text" name="chain_id" value="{{ old('chain_id', $conn?->settings_json['chain_id'] ?? '') }}" placeholder="örn: CHAIN-123" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Vendor ID</label>
                        <input type="text" name="vendor_id" value="{{ old('vendor_id', $conn?->settings_json['vendor_id'] ?? '') }}" placeholder="örn: VENDOR-987" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Client ID</label>
                        <input type="text" name="client_id" value="" autocomplete="off" placeholder="Mevcut deger gizlidir" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Client Secret</label>
                        <input type="password" name="client_secret" value="" autocomplete="new-password" placeholder="Mevcut deger gizlidir" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                @elseif($providerKey === 'trendyol_yemek')
                    <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-700">
                        Trendyol bağlantısı için sadece aşağıdaki alanlar yeterli. Token üretme/enable gibi işlemler bu bilgileri kaydettikten sonra yapılır.
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Seller ID (Cari ID)</label>
                        <input type="text" name="seller_id" value="{{ old('seller_id', (string)($credentials['seller_id'] ?? '')) }}" placeholder="örn: 6719778" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Integrator name</label>
                        <input type="text" name="integrator_name" value="{{ old('integrator_name', $conn?->settings_json['integrator_name'] ?? '') }}" placeholder="ör. kuryeapp" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">API Key</label>
                        <input type="text" name="api_key" value="" autocomplete="off" placeholder="Güvenlik için gösterilmez. Güncellemek için yeni değer girin." class="w-full rounded border border-slate-300 px-3 py-2 text-sm font-mono">
                        <p class="mt-1 text-xs {{ !empty($credentials['api_key']) ? 'text-emerald-700' : 'text-amber-800' }}">
                            {{ !empty($credentials['api_key']) ? 'Kayıtlı (gösterilmez).' : 'Eksik.' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">API Secret</label>
                        <input type="password" name="api_secret" value="" autocomplete="new-password" placeholder="Güvenlik için gösterilmez. Güncellemek için yeni değer girin." class="w-full rounded border border-slate-300 px-3 py-2 text-sm font-mono">
                        <p class="mt-1 text-xs {{ !empty($credentials['api_secret']) ? 'text-emerald-700' : 'text-amber-800' }}">
                            {{ !empty($credentials['api_secret']) ? 'Kayıtlı (gösterilmez).' : 'Eksik.' }}
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Executor user e-mail</label>
                        <input type="email" name="executor_user_email" value="{{ old('executor_user_email', $conn?->settings_json['executor_user_email'] ?? '') }}" placeholder="örn: entegrasyon@firma.com" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Access token (Bearer)</label>
                        <input type="password" name="access_token" value="" autocomplete="new-password" placeholder="Token varsa buraya yapıştırın (yoksa Create Integrator ile üretilecek)" class="w-full rounded border border-slate-300 px-3 py-2 text-sm font-mono">
                        <p class="mt-1 text-xs {{ $hasAccessToken ? 'text-emerald-700' : 'text-amber-800' }}">
                            {{ $hasAccessToken ? 'Token kayıtlı (gösterilmez).' : 'Token yok.' }}
                        </p>
                    </div>

                    <details class="md:col-span-2 rounded-lg border border-slate-200 bg-white px-4 py-3">
                        <summary class="cursor-pointer text-sm font-medium text-slate-800 list-none [&::-webkit-details-marker]:hidden flex items-center justify-between">
                            <span>Gelişmiş</span>
                            <span class="text-xs text-slate-500">opsiyonel</span>
                        </summary>
                        <div class="mt-3 space-y-2">
                            <div>
                                <label class="block text-sm text-slate-600 mb-1">TGO API Base URL</label>
                                <input type="url" name="api_base_url" value="{{ old('api_base_url', $conn?->settings_json['api_base_url'] ?? 'https://api.tgoapis.com') }}" placeholder="https://api.tgoapis.com" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                                <p class="text-xs text-slate-500 mt-1">Stage: <code class="bg-slate-100 px-1 rounded">https://stageapi.tgoapis.com</code></p>
                            </div>
                            @if(!$appUrlLooksPublic)
                                <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                    <p class="font-medium">Uyarı: APP_URL dışarıdan erişilebilir görünmüyor.</p>
                                    <p class="mt-1">Mevcut: <code class="bg-white/60 px-1 rounded">{{ $appUrl !== '' ? $appUrl : '—' }}</code>. Lokal (localhost/127.0.0.1) ise TGO webhook gönderemez.</p>
                                </div>
                            @endif
                        </div>
                    </details>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <input type="hidden" name="is_active" value="0">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" @checked(old('is_active', $isActive))>
                    <span>Entegrasyon aktif</span>
                </label>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm hover:bg-slate-800">Kaydet</button>
            </div>
        </form>

        @if($providerKey === 'trendyol_yemek')
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <h4 class="text-sm font-semibold text-slate-800 mb-2">Bağlantı adımları</h4>
                <p class="text-xs text-slate-600 mb-3">Sadece bilgileri girip <strong>Kaydet</strong> deyin, sonra <strong>Otomatik Bağla</strong> ile kurulum tamamlansın.</p>

                <div class="flex flex-wrap gap-2">
                    <form method="post" action="{{ route('restaurant.integrations.tgo.auto_connect', [], false) }}">
                        @csrf
                        <button type="submit" class="rounded-md bg-panel-accent px-3 py-2 text-xs font-semibold text-white hover:opacity-95">
                            Otomatik Bağla
                        </button>
                    </form>

                    <form method="post" action="{{ route('restaurant.integrations.tgo.create_integrator', [], false) }}">
                        @csrf
                        <button type="submit" class="rounded-md bg-slate-900 px-3 py-2 text-xs font-medium text-white hover:bg-slate-800"
                            @disabled(!($hasApiKeySecret && $hasSellerId && $hasIntegratorMeta))>
                            Create Integrator
                        </button>
                    </form>

                    <form method="post" action="{{ route('restaurant.integrations.tgo.enable', [], false) }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-800 hover:bg-emerald-100"
                            @disabled(!($hasAccessToken && $hasApiKeySecret && $hasIntegratorMeta))>
                            Enable
                        </button>
                    </form>

                    <form method="post" action="{{ route('restaurant.integrations.tgo.refresh_token', [], false) }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-100"
                            @disabled(!($hasAccessToken && $hasApiKeySecret && $hasIntegratorMeta))>
                            Refresh Token
                        </button>
                    </form>

                    <form method="post" action="{{ route('restaurant.integrations.tgo.test_order', [], false) }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 hover:bg-amber-100"
                            @disabled(!($hasAccessToken && $hasApiKeySecret && $hasSellerId && $hasIntegratorMeta))>
                            Test Order
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <form method="post" action="{{ route('restaurant.integrations.regenerateToken', $providerKey, false) }}" class="inline" onsubmit="return confirm('Token değişirse eski entegrasyonlar çalışmayı durdurur. Devam?');">
            @csrf
            <button type="submit" class="text-sm text-amber-700 hover:underline">Yeni token üret</button>
        </form>
    </div>

    <div class="mt-8 border-t border-slate-100 pt-6">
        <h3 class="text-sm font-semibold text-slate-800 mb-3">Ürün eşlemesi ({{ $meta['label'] }})</h3>
        <p class="text-xs text-slate-500 mb-3">Platformdaki ürün kodunu (SKU / remote id) yerel menüdeki ürüne bağlayın; webhook gövdesinde <code class="bg-slate-100 px-1 rounded">external_sku</code> kullanılır.</p>

        <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <h4 class="text-sm font-semibold text-slate-800 mb-2">Toplu eşleme import (CSV/JSON)</h4>
            <p class="text-xs text-slate-600 mb-2">
                CSV için header beklenir: <code class="bg-white px-1 rounded">external_sku,product_id</code>.
                JSON için: <code class="bg-white px-1 rounded">[{ "external_sku": "...", "product_id": 123 }]</code>.
            </p>
            <p class="text-xs text-slate-500 mb-2">
                <a class="text-amber-700 hover:underline" target="_blank" rel="noopener noreferrer" href="https://developers.tgoapps.com/">
                    Resmi TGO dokümanı
                </a>
                <span class="text-slate-400">·</span>
                <span>Trendyol için <code class="bg-white px-1 rounded">external_sku</code> = <code class="bg-white px-1 rounded">productId</code></span>
            </p>
            <div class="flex flex-wrap items-center gap-3 mb-2">
                <a href="{{ route('restaurant.integrations.productMaps.template', $providerKey) }}"
                   class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-100">
                    CSV şablonu indir
                </a>
                <span class="text-xs text-slate-500">Şablon aktif ürünleri listeler; <code class="bg-white px-1 rounded">external_sku</code> kolonunu doldurup import edin.</span>
            </div>
            <form method="post" action="{{ route('restaurant.integrations.productMaps.import', $providerKey, false) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="block text-xs text-slate-600 mb-1">Dosya</label>
                    <input type="file" name="file" accept=".csv,.json" required class="text-xs">
                </div>
                <button type="submit" class="rounded-md bg-slate-800 px-3 py-2 text-xs font-medium text-white hover:bg-slate-700">
                    Import et
                </button>
            </form>
        </div>

        @if($maps->isNotEmpty())
            <div class="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="py-2 px-3">Platform kodu</th>
                            <th class="py-2 px-3">Yerel ürün</th>
                            <th class="py-2 px-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($maps as $map)
                            <tr class="border-t border-slate-100">
                                <td class="py-2 px-3 font-mono text-xs">{{ $map->external_sku }}</td>
                                <td class="py-2 px-3">{{ $map->product?->name ?? '—' }}</td>
                                <td class="py-2 px-3 text-right">
                                    <form method="post" action="{{ route('restaurant.integrations.productMaps.destroy', [$providerKey, $map]) }}" class="inline" onsubmit="return confirm('Silinsin mi?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-amber-700 hover:underline text-xs">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if($products->isEmpty())
            <p class="text-sm text-slate-500">Önce menüye aktif ürün ekleyin.</p>
        @else
            <form method="post" action="{{ route('restaurant.integrations.productMaps.store', $providerKey, false) }}" class="flex flex-wrap items-end gap-3 text-sm">
                @csrf
                <div>
                    <label class="block text-slate-600 mb-1">Platform ürün kodu</label>
                    <input name="external_sku" required maxlength="190" class="rounded border border-slate-300 px-3 py-2 w-48" placeholder="ör. YS-12345">
                </div>
                <div>
                    <label class="block text-slate-600 mb-1">Yerel ürün</label>
                    <select name="product_id" required class="rounded border border-slate-300 px-3 py-2 min-w-[12rem]">
                        <option value="">Seçin</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-slate-800 px-3 py-2 text-white text-sm hover:bg-slate-700">Eşle</button>
            </form>
        @endif
    </div>
</section>
