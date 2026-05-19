<?php

declare(strict_types=1);

namespace App\Support;

/**
 * SSRF riskini azaltmak için dışarı giden HTTP URL doğrulaması.
 */
final class SafeOutboundUrl
{
    public static function isAllowed(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);
        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if ($scheme !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || $host === 'localhost') {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return ! self::isPrivateIp($host);
        }

        if (str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return false;
        }

        $resolved = @gethostbynamel($host);
        if (is_array($resolved)) {
            foreach ($resolved as $ip) {
                if (self::isPrivateIp($ip)) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function isPrivateIp(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }

        return false;
    }
}
