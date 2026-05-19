<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Webhook: IP kısıtı, tekrarlayan gövde (replay) engeli.
 */
final class WebhookGuard
{
    /**
     * @param  array<string, mixed>  $settings
     */
    public static function assertConnectionAllowed(Request $request, array $settings, int $connectionId): bool
    {
        self::assertIpAllowlist($request, $settings);

        return self::isDuplicateReplay($request, $connectionId);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private static function assertIpAllowlist(Request $request, array $settings): void
    {
        $allowed = $settings['webhook_allowed_ips'] ?? null;
        if (! is_array($allowed) || $allowed === []) {
            return;
        }

        $ip = (string) $request->ip();
        foreach ($allowed as $entry) {
            if (is_string($entry) && trim($entry) === $ip) {
                return;
            }
        }

        throw new HttpException(403, 'Webhook IP adresi izinli değil.');
    }

    /** Aynı gövde 10 dk içinde tekrar gelirse true (replay / çift tıklama). */
    public static function isDuplicateReplay(Request $request, int $connectionId): bool
    {
        $body = $request->getContent();
        $key = 'webhook:dedupe:'.$connectionId.':'.hash('sha256', $body);

        if (Cache::has($key)) {
            return true;
        }

        Cache::put($key, 1, now()->addMinutes(10));

        return false;
    }
}
