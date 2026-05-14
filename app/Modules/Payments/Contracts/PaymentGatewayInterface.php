<?php

namespace App\Modules\Payments\Contracts;

use App\Modules\Orders\Models\Order;

interface PaymentGatewayInterface
{
    /**
     * @return array{provider?: string, transaction_id?: string, redirect_url?: string|null}
     */
    public function startCheckout(Order $order, array $options = []): array;

    public function verifyCallback(array $payload): bool;
}
