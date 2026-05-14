<?php

namespace App\Modules\Payments\Services;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;

/**
 * Çevrimiçi ödeme sağlayıcısı eklenene kadar yer tutucu.
 */
final class NullPaymentGateway implements PaymentGatewayInterface
{
    public function startCheckout(Order $order, array $options = []): array
    {
        return [
            'provider' => 'null',
            'transaction_id' => 'demo-'.$order->id,
            'redirect_url' => null,
        ];
    }

    public function verifyCallback(array $payload): bool
    {
        return false;
    }
}
