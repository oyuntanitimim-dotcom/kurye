<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;

/**
 * Geliştirme / pilot: PSP yönlendirmesi yok, sipariş anında "tamamlandı" kabulü.
 */
final class LocalPaymentGateway implements PaymentGatewayInterface
{
    public function startCheckout(Order $order, array $options = []): array
    {
        return [
            'provider' => 'local',
            'transaction_id' => 'local-'.$order->id.'-'.bin2hex(random_bytes(4)),
            'redirect_url' => null,
        ];
    }

    public function verifyCallback(array $payload): bool
    {
        return false;
    }
}
