<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Ham JSON gövdesi üzerinden HMAC-SHA256 (settings_json.webhook_secret).
 *
 * Başlık: X-Integration-Signature — düz onaltılık veya "sha256=" öneki.
 */
final class WebhookRequestSignature
{
    /**
     * @throws HttpException 401
     */
    public static function assertValidWhenSecretConfigured(Request $request, ?string $secret): void
    {
        $secret = is_string($secret) ? trim($secret) : '';
        if ($secret === '') {
            if (app()->environment('production')) {
                throw new HttpException(503, 'Webhook HMAC gizli anahtarı yapılandırılmamış (webhook_secret).');
            }

            return;
        }

        $header = (string) $request->header('X-Integration-Signature', '');
        if ($header === '') {
            throw new HttpException(401, 'X-Integration-Signature gerekli (webhook gizli anahtarı tanımlı).');
        }

        $raw = $request->getContent();
        $expected = hash_hmac('sha256', $raw, $secret);
        $provided = self::normalizeHex($header);

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            throw new HttpException(401, 'Geçersiz webhook imzası.');
        }
    }

    private static function normalizeHex(string $header): string
    {
        $h = trim($header);
        if (str_starts_with(strtolower($h), 'sha256=')) {
            $h = trim(substr($h, 7));
        }

        return strtolower($h);
    }
}
