<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Modules\Integrations\Models\IntegrationConnection;
use App\Modules\Integrations\Models\IntegrationExternalOrder;
use App\Modules\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Hazır / iptal durumunda isteğe bağlı iş ortağı URL'sine JSON POST (ayar: order_status_webhook_url).
 * HTTP 5xx ve 429 için önce Http::retry; sync dışı kuyrukta ek olarak release() ile gecikmeli yeniden deneme.
 * ShouldBeUnique: aynı sipariş ve durum için eşzamanlı çift dispatch tek job’a düşer.
 */
class PushMarketplaceOrderStatusJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public function __construct(
        public int $orderId,
        public string $status
    ) {
        $this->tries = max(1, (int) data_get(config('marketplace_integrations.status_push'), 'queue_max_attempts', 4));
        $this->onQueue((string) data_get(config('marketplace_integrations.status_push'), 'queue', 'integrations'));
    }

    public function uniqueId(): string
    {
        return $this->orderId.'|'.$this->status;
    }

    public function uniqueFor(): int
    {
        return max(60, (int) data_get(config('marketplace_integrations.status_push'), 'unique_lock_seconds', 900));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('marketplace_status_push_job_failed', [
            'order_id' => $this->orderId,
            'status' => $this->status,
            'exception' => $exception?->getMessage(),
        ]);
    }

    public function handle(): void
    {
        $order = Order::query()->find($this->orderId);
        if ($order === null) {
            return;
        }

        $ext = IntegrationExternalOrder::query()->where('order_id', $order->id)->first();
        if ($ext === null) {
            return;
        }

        $connection = IntegrationConnection::query()
            ->where('firm_id', $order->firm_id)
            ->where('restaurant_id', $order->restaurant_id)
            ->where('provider', $ext->provider)
            ->where('is_active', true)
            ->first();

        $url = $connection?->settings_json['order_status_webhook_url'] ?? null;
        if (! is_string($url) || $url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            Log::info('marketplace_status_push_skipped', [
                'order_id' => $order->id,
                'provider' => $ext->provider,
                'external_order_id' => $ext->external_order_id,
                'status' => $this->status,
            ]);

            return;
        }

        $token = (string) ($connection->settings_json['webhook_token'] ?? '');

        $cfg = config('marketplace_integrations.status_push', []);
        $timeout = max(3, (int) ($cfg['timeout_seconds'] ?? 12));
        $httpRetries = max(1, (int) ($cfg['http_retries'] ?? 3));
        $retryMs = max(0, (int) ($cfg['http_retry_sleep_ms'] ?? 400));

        $response = Http::timeout($timeout)
            ->retry($httpRetries, $retryMs, function ($exception): bool {
                if (! $exception instanceof RequestException) {
                    return true;
                }
                $failed = $exception->response;
                if ($failed === null) {
                    return true;
                }
                $code = $failed->status();

                return $code >= 500 || $code === 429;
            }, throw: false)
            ->withHeaders([
                'X-Integration-Token' => $token,
                'Accept' => 'application/json',
            ])
            ->asJson()
            ->post($url, [
                'event' => 'order.status_changed',
                'provider' => $ext->provider,
                'external_order_id' => $ext->external_order_id,
                'internal_order_id' => $order->id,
                'status' => $this->status,
            ]);

        if (! $response->successful()) {
            Log::warning('marketplace_status_webhook_http_error', [
                'order_id' => $order->id,
                'provider' => $ext->provider,
                'http_status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (
                $this->job
                && $this->queueDriverSupportsDelayedRetry()
                && (bool) data_get(config('marketplace_integrations.status_push'), 'queue_retry_enabled', true)
                && ($response->serverError() || $response->status() === 429)
                && $this->attempts() < $this->tries
            ) {
                $this->release($this->queueReleaseDelaySeconds());

                return;
            }
        }
    }

    private function queueDriverSupportsDelayedRetry(): bool
    {
        return ! in_array((string) config('queue.default'), ['sync', 'null'], true);
    }

    private function queueReleaseDelaySeconds(): int
    {
        $delays = config('marketplace_integrations.status_push.queue_release_seconds');
        if (! is_array($delays) || $delays === []) {
            $delays = [90, 300, 600];
        }
        $idx = max(0, min($this->attempts() - 1, count($delays) - 1));

        return max(1, (int) $delays[$idx]);
    }
}