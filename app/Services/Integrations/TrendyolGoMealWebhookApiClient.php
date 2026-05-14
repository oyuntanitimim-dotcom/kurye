<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

final class TrendyolGoMealWebhookApiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $apiSecret,
        private readonly int $timeoutSeconds = 20,
    ) {}

    /**
     * Create an integrator for webhook events (returns bearer token).
     *
     * @return array<string,mixed>
     */
    public function createIntegrator(
        string $integratorName,
        string $executorUserEmail,
        string $webhookBaseUrl,
        string $webhookDestinationPath,
        string $integrationToken,
        string $sellerId,
    ): array {
        $base = rtrim($webhookBaseUrl, '/');
        $destinationPath = $this->normalizePath($webhookDestinationPath);
        $fullDestinationUrl = $base.$destinationPath;

        $payload = [
            'integratorName' => $integratorName,
            'baseUrl' => $base,
            'destinationUrl' => $fullDestinationUrl,
            'sellers' => [$sellerId],
            'secrets' => [
                'httpHeaders' => [
                    'X-Integration-Token' => $integrationToken,
                ],
            ],
        ];

        $res = $this->sellerAuthed($integratorName, $executorUserEmail)
            ->post('/integrator/order/meal/webhook/create', $payload);

        $res->throw();

        return is_array($res->json()) ? $res->json() : ['ok' => true, 'status_code' => $res->status()];
    }

    /**
     * @return array<string,mixed>
     */
    public function refreshIntegratorToken(string $integratorName, string $executorUserEmail, string $bearerToken): array
    {
        $res = $this->bearerAuthed($integratorName, $executorUserEmail, $bearerToken)
            ->post("/integrator/order/meal/webhook/{$integratorName}/token/refresh");

        $res->throw();

        return is_array($res->json()) ? $res->json() : ['ok' => true, 'status_code' => $res->status()];
    }

    /**
     * @return array<string,mixed>
     */
    public function addSeller(string $integratorName, string $executorUserEmail, string $bearerToken, string $sellerId): array
    {
        $res = $this->bearerAuthed($integratorName, $executorUserEmail, $bearerToken)
            ->patch("/integrator/order/meal/webhook/{$integratorName}/seller/add", [
                'sellerId' => $sellerId,
            ]);

        $res->throw();

        return is_array($res->json()) ? $res->json() : ['ok' => true, 'status_code' => $res->status()];
    }

    /**
     * @return array<string,mixed>
     */
    public function enableIntegration(string $integratorName, string $executorUserEmail, string $bearerToken): array
    {
        $res = $this->bearerAuthed($integratorName, $executorUserEmail, $bearerToken)
            ->patch("/integrator/order/meal/webhook/{$integratorName}/enable");

        $res->throw();

        return is_array($res->json()) ? $res->json() : ['ok' => true, 'status_code' => $res->status()];
    }

    /**
     * @return array<string,mixed>
     */
    public function createTestOrder(string $integratorName, string $executorUserEmail, string $bearerToken, string $sellerId): array
    {
        $res = $this->bearerAuthed($integratorName, $executorUserEmail, $bearerToken)
            ->post("/integrator/order/meal/webhook/{$integratorName}/order/test", [
                'sellerId' => $sellerId,
            ]);

        $res->throw();

        return is_array($res->json()) ? $res->json() : ['ok' => true, 'status_code' => $res->status()];
    }

    private function sellerAuthed(string $integratorName, string $executorUserEmail): PendingRequest
    {
        // TGO expects executor info + agent/integrator identifier headers.
        // Some environments require `x-agentname` (newer) while others accept `x-integratorname` (older).
        return $this->base()
            ->withHeaders([
                'x-agentname' => $integratorName,
                'x-integratorname' => $integratorName,
                'x-executor-user' => $executorUserEmail,
            ])
            ->withBasicAuth($this->apiKey, $this->apiSecret);
    }

    private function bearerAuthed(string $integratorName, string $executorUserEmail, string $bearerToken): PendingRequest
    {
        $token = preg_replace('/^Bearer\s+/i', '', trim($bearerToken)) ?? '';
        return $this->base()
            ->withHeaders([
                'x-agentname' => $integratorName,
                'x-integratorname' => $integratorName,
                'x-executor-user' => $executorUserEmail,
            ])
            ->withToken($token);
    }

    private function base(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeoutSeconds)
            ->retry(3, 300, function (\Throwable $exception): bool {
                $res = $exception instanceof RequestException ? $exception->response : null;
                if ($res === null) {
                    return false;
                }
                $code = $res->status();

                return $code >= 500 || $code === 429;
            }, throw: true);
    }

    private function normalizePath(string $path): string
    {
        $t = trim($path);
        if ($t === '') {
            throw new \InvalidArgumentException('Webhook destination path boş olamaz.');
        }

        return str_starts_with($t, '/') ? $t : '/'.$t;
    }
}

