<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

final class YemeksepetiPartnerApiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly int $timeoutSeconds = 15,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function updateOutletStatus(string $chainId, string $vendorId, string $status, ?string $closedReason, ?string $closedUntil): array
    {
        $payload = [
            'status' => $status,
        ];

        // Dokümanda: OPEN iken kapatma nedeni/tarih alanları gönderilmez.
        if (strtoupper($status) !== 'OPEN') {
            if ($closedReason !== null && $closedReason !== '') {
                $payload['closed_reason'] = $closedReason;
            }
            if ($closedUntil !== null && $closedUntil !== '') {
                $payload['closed_until'] = $closedUntil;
            }
        }

        $res = $this->authed()->put("/v2/chains/{$chainId}/vendors/{$vendorId}/status", $payload);
        $res->throw();

        return is_array($res->json()) ? $res->json() : ['ok' => true, 'status_code' => $res->status()];
    }

    /**
     * @return array<string,mixed>
     */
    public function getOutletStatus(string $chainId, string $vendorId): array
    {
        $res = $this->authed()->get("/v2/chains/{$chainId}/vendors/{$vendorId}/status");
        $res->throw();

        return is_array($res->json()) ? $res->json() : ['ok' => true, 'status_code' => $res->status()];
    }

    /**
     * @param  array<string,mixed>  $payload  {"products":[{"sku":"...","active":true,"price":12.3}, ...]}
     * @return array<string,mixed>
     */
    public function updateCatalogProducts(string $chainId, string $vendorId, array $payload): array
    {
        $res = $this->authed()->put("/v2/chains/{$chainId}/vendors/{$vendorId}/catalog", $payload);
        $res->throw();

        return is_array($res->json()) ? $res->json() : ['ok' => true, 'status_code' => $res->status()];
    }

    public function getCatalogJobStatus(string $chainId, string $jobId): array
    {
        $res = $this->authed()->get("/v2/chains/{$chainId}/catalog/jobs/{$jobId}");
        $res->throw();

        return is_array($res->json()) ? $res->json() : ['ok' => true, 'status_code' => $res->status()];
    }

    /**
     * @param  array<string,mixed>  $payload  {"vendors":[...], "type":"STRIKETHROUGH", ...}
     * @return array<string,mixed>
     */
    public function updatePromotion(string $chainId, array $payload): array
    {
        $res = $this->authed()->put("/v2/chains/{$chainId}/promotion", $payload);
        $res->throw();

        return is_array($res->json()) ? $res->json() : ['ok' => true, 'status_code' => $res->status()];
    }

    public function getPromotionJobStatus(string $chainId, string $jobId): array
    {
        $res = $this->authed()->get("/v2/chains/{$chainId}/promotion/jobs/{$jobId}");
        $res->throw();

        return is_array($res->json()) ? $res->json() : ['ok' => true, 'status_code' => $res->status()];
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public function startAsyncJob(string $path, array $payload): array
    {
        $res = $this->authed()->post($this->normalizePath($path), $payload);
        $res->throw();

        return is_array($res->json()) ? $res->json() : ['ok' => true, 'status_code' => $res->status()];
    }

    /**
     * @param  array<string,mixed>  $query
     * @return array<string,mixed>
     */
    public function getJobStatus(string $path, array $query = []): array
    {
        $res = $this->authed()->get($this->normalizePath($path), $query);
        $res->throw();

        return is_array($res->json()) ? $res->json() : ['ok' => true, 'status_code' => $res->status()];
    }

    private function authed(): PendingRequest
    {
        $token = $this->fetchAccessToken();

        return Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->acceptJson()
            ->withToken($token)
            ->timeout($this->timeoutSeconds)
            ->asJson();
    }

    private function fetchAccessToken(): string
    {
        $cacheKey = 'yemeksepeti:access_token:'.sha1($this->baseUrl.'|'.$this->clientId);
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $res = Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->acceptJson()
            ->asForm()
            ->timeout($this->timeoutSeconds)
            ->retry(
                3,
                250,
                function (\Throwable $exception): bool {
                    // Token alımı başarısız olursa 5xx / 429 durumlarında yeniden deniyoruz.
                    $response = $exception instanceof \Illuminate\Http\Client\RequestException
                        ? $exception->response
                        : null;
                    if ($response === null) {
                        return false;
                    }
                    $code = $response->status();
                    return $code >= 500 || $code === 429;
                },
                throw: true
            )
            ->post('/v2/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

        $res->throw();

        $token = (string) ($res->json('access_token') ?? '');
        if ($token === '') {
            throw new \RuntimeException('Yemeksepeti access_token bos dondu.');
        }

        $expiresIn = (int) ($res->json('expires_in') ?? 3600);
        $ttlSeconds = max(60, $expiresIn - 60);
        Cache::put($cacheKey, $token, $ttlSeconds);

        return $token;
    }

    private function normalizePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Endpoint path bos olamaz.');
        }

        return str_starts_with($trimmed, '/') ? $trimmed : '/'.$trimmed;
    }
}
