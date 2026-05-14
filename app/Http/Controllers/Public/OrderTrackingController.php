<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use Illuminate\Contracts\View\View;

class OrderTrackingController extends Controller
{
    public function show(string $token): View
    {
        $order = Order::query()
            ->where('tracking_token', $token)
            ->with(['firm', 'restaurant', 'statusHistories', 'courier'])
            ->first();

        if ($order === null) {
            abort(404);
        }

        $histories = $order->statusHistories()->orderBy('created_at')->get();

        return view('public.order-tracking', [
            'title' => 'Sipariş takibi',
            'order' => $order,
            'histories' => $histories,
            'statusLabel' => OrderStatus::tryFrom($order->status)?->label() ?? $order->status,
            'phoneMasked' => $this->maskPhone($order->customer_phone),
            'revoked' => $order->tracking_revoked_at !== null,
        ]);
    }

    private function maskPhone(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '—';
        }
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return strlen($digits) >= 4 ? '***'.substr($digits, -4) : '***';
    }
}
