<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Enums\OrderStatus;
use App\Modules\Integrations\Models\IntegrationConnection;
use App\Modules\Integrations\Models\IntegrationExternalOrder;
use App\Modules\Integrations\Services\MarketplaceIngestService;
use App\Modules\Integrations\Support\WebhookRequestSignature;
use App\Services\Integrations\TrendyolGoMealWebhookPayloadMapper;
use App\Modules\Orders\Services\OrderStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IntegrationWebhookController extends Controller
{
    public function __construct(
        private readonly MarketplaceIngestService $ingestService,
        private readonly OrderStateService $orderStateService,
    ) {}

    /**
     * X-Integration-Token zorunlu (settings_json.webhook_token). İsteğe bağlı X-Firm-Id,
     * kayıtla eşleşmezse reddedilir (eski istemciler için).
     *
     * settings_json.webhook_secret doluysa ham gövde üzerinden HMAC-SHA256 zorunlu:
     * başlık X-Integration-Signature (onaltılık veya "sha256=" öneki).
     *
     * Rota: throttle:integration-webhook (IP başına dakika, config marketplace_integrations.webhook_per_minute).
     * Gövde boyutu: marketplace_integrations.webhook_max_body_bytes (varsayılan 512 KiB).
     */
    public function handle(Request $request, string $provider): JsonResponse
    {
        if (! array_key_exists($provider, (array) config('marketplace_integrations.providers', []))) {
            abort(404, 'Bilinmeyen entegrasyon sağlayıcısı.');
        }

        $requestId = (string) Str::uuid();

        $maxBytes = (int) config('marketplace_integrations.webhook_max_body_bytes', 524288);
        if ($maxBytes > 0 && strlen($request->getContent()) > $maxBytes) {
            abort(413, 'İstek gövdesi çok büyük.');
        }

        $token = (string) $request->header('X-Integration-Token', '');
        if ($token === '') {
            abort(422, 'X-Integration-Token gerekli.');
        }

        $connection = IntegrationConnection::query()
            ->where('provider', $provider)
            ->where('is_active', true)
            ->where('settings_json->webhook_token', $token)
            ->first();

        if ($connection === null) {
            abort(404, 'Entegrasyon bulunamadı.');
        }

        // UI: show last successful webhook receive time.
        $settings = is_array($connection->settings_json) ? $connection->settings_json : [];
        $settings['last_webhook_received_at'] = now()->toISOString();
        $connection->settings_json = $settings;
        $connection->save();

        $headerFirmId = (int) $request->header('X-Firm-Id', 0);
        if ($headerFirmId > 0 && $headerFirmId !== (int) $connection->firm_id) {
            abort(403, 'X-Firm-Id eşleşmiyor.');
        }

        $webhookSecret = isset($settings['webhook_secret']) && is_string($settings['webhook_secret'])
            ? $settings['webhook_secret']
            : null;
        WebhookRequestSignature::assertValidWhenSecretConfigured($request, $webhookSecret);

        try {
            $payload = $request->all();

            // Lightweight monitoring fields for the restaurant panel.
            $eventType = (string) ($payload['eventType'] ?? ($payload['event_type'] ?? ($payload['type'] ?? ($payload['event'] ?? ''))));
            $settings['webhook_stats'] = is_array($settings['webhook_stats'] ?? null) ? $settings['webhook_stats'] : [];
            $settings['webhook_stats']['total'] = (int) ($settings['webhook_stats']['total'] ?? 0) + 1;
            $dayKey = now()->format('Y-m-d');
            $byDay = is_array(($settings['webhook_stats']['by_day'] ?? null)) ? $settings['webhook_stats']['by_day'] : [];
            $byDay[$dayKey] = (int) ($byDay[$dayKey] ?? 0) + 1;
            // Keep last 10 days.
            if (count($byDay) > 10) {
                ksort($byDay);
                $byDay = array_slice($byDay, -10, null, true);
            }
            $settings['webhook_stats']['by_day'] = $byDay;
            $settings['webhook_stats']['last_event_type'] = $eventType !== '' ? $eventType : null;
            $connection->settings_json = $settings;
            $connection->save();

            if ($provider === 'trendyol_yemek') {
                $mapped = TrendyolGoMealWebhookPayloadMapper::toNormalizedPayload($payload);
                if ($mapped !== null) {
                    $payload = $mapped;
                } else {
                    $externalOrderId = TrendyolGoMealWebhookPayloadMapper::extractExternalOrderId($payload);
                    $next = TrendyolGoMealWebhookPayloadMapper::mapEventToOrderStatus($payload);

                    if ($externalOrderId === '' || $next === null) {
                        return response()->json([
                            'ok' => true,
                            'request_id' => $requestId,
                            'skipped' => true,
                        ])->header('X-Request-Id', $requestId);
                    }

                    $settings['webhook_stats'] = is_array($settings['webhook_stats'] ?? null) ? $settings['webhook_stats'] : [];
                    $settings['webhook_stats']['last_external_order_id'] = $externalOrderId;
                    $connection->settings_json = $settings;
                    $connection->save();

                    $ext = IntegrationExternalOrder::query()
                        ->where('firm_id', (int) $connection->firm_id)
                        ->where('provider', $provider)
                        ->where('external_order_id', $externalOrderId)
                        ->first();

                    $order = $ext?->order;
                    if ($order === null) {
                        return response()->json([
                            'ok' => true,
                            'request_id' => $requestId,
                            'skipped' => true,
                        ])->header('X-Request-Id', $requestId);
                    }

                    $current = OrderStatus::tryFrom((string) $order->status);
                    if ($current !== null && in_array($current, [OrderStatus::Delivered, OrderStatus::Cancelled], true)) {
                        return response()->json([
                            'ok' => true,
                            'request_id' => $requestId,
                            'updated' => false,
                            'skipped' => true,
                            'reason' => 'terminal_status',
                        ])->header('X-Request-Id', $requestId);
                    }
                    if ($current === $next) {
                        return response()->json([
                            'ok' => true,
                            'request_id' => $requestId,
                            'updated' => false,
                        ])->header('X-Request-Id', $requestId);
                    }

                    if (! $this->isForwardProgress($current, $next)) {
                        return response()->json([
                            'ok' => true,
                            'request_id' => $requestId,
                            'updated' => false,
                            'skipped' => true,
                            'reason' => 'status_regression',
                        ])->header('X-Request-Id', $requestId);
                    }

                    $this->orderStateService->transition($order, $next, [
                        'source' => 'trendyol_go_webhook',
                        'event_type' => (string) ($payload['eventType'] ?? ($payload['event_type'] ?? ($payload['type'] ?? ($payload['event'] ?? '')))),
                        'external_order_id' => $externalOrderId,
                    ]);

                    $settings['webhook_stats'] = is_array($settings['webhook_stats'] ?? null) ? $settings['webhook_stats'] : [];
                    $settings['webhook_stats']['last_status_update'] = $next->value;
                    $connection->settings_json = $settings;
                    $connection->save();

                    return response()->json([
                        'ok' => true,
                        'request_id' => $requestId,
                        'updated' => true,
                        'status' => $next->value,
                    ])->header('X-Request-Id', $requestId);
                }
            }

            $order = $this->ingestService->ingestFromPayload(
                (int) $connection->firm_id,
                $provider,
                $payload,
                (int) $connection->restaurant_id
            );

            if ($provider === 'trendyol_yemek') {
                $settings['webhook_stats'] = is_array($settings['webhook_stats'] ?? null) ? $settings['webhook_stats'] : [];
                $settings['webhook_stats']['last_external_order_id'] = (string) ($payload['external_order_id'] ?? '');
                $connection->settings_json = $settings;
                $connection->save();
            }

            return response()->json([
                'ok' => true,
                'request_id' => $requestId,
                'order_id' => $order->id,
            ])->header('X-Request-Id', $requestId);
        } catch (ValidationException $e) {
            return response()->json([
                'ok' => false,
                'request_id' => $requestId,
                'errors' => $e->errors(),
            ], 422)->header('X-Request-Id', $requestId);
        }
    }

    private function isForwardProgress(?OrderStatus $current, OrderStatus $next): bool
    {
        if ($current === null) {
            return true;
        }

        $rank = [
            OrderStatus::Pending->value => 10,
            OrderStatus::Accepted->value => 20,
            OrderStatus::Preparing->value => 30,
            OrderStatus::Ready->value => 40,
            OrderStatus::CourierAssigned->value => 50,
            OrderStatus::CourierAccepted->value => 60,
            OrderStatus::PickedUp->value => 70,
            OrderStatus::OnTheWay->value => 80,
            OrderStatus::Delivered->value => 90,
            OrderStatus::Cancelled->value => 90,
        ];

        $cur = $rank[$current->value] ?? 0;
        $nxt = $rank[$next->value] ?? 0;

        return $nxt >= $cur;
    }
}
