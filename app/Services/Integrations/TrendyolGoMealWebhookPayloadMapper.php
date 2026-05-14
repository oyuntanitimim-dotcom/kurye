<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Enums\OrderStatus;

/**
 * Trendyol Go (TGO) Meal webhook payloads → internal "normalize gövde".
 *
 * Internal format is documented in `resources/views/restaurant/integrations.blade.php` technical summary.
 *
 * We currently ingest only "created" (new order) events.
 */
final class TrendyolGoMealWebhookPayloadMapper
{
    /**
     * @param  array<string,mixed>  $incoming
     * @return array<string,mixed>|null  null means: ignore/skipped (not a created event / no usable data)
     */
    public static function toNormalizedPayload(array $incoming): ?array
    {
        $eventType = self::extractEventType($incoming);
        $payload = self::extractPayloadNode($incoming);

        if ($eventType !== null && strtolower($eventType) !== 'created') {
            return null;
        }

        $externalOrderId = self::extractExternalOrderId($incoming);
        if ($externalOrderId === '') {
            return null;
        }

        $customerName = self::extractCustomerName($payload);
        $customerPhone = self::extractCustomerPhone($payload);
        $notes = self::extractCustomerNote($payload);

        $addressText = self::extractAddressText($payload);
        $coords = self::extractLatLng($payload);

        $items = self::extractItems($payload);
        if ($items === []) {
            return null;
        }

        $normalized = [
            'external_order_id' => $externalOrderId,
            'items' => $items,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'delivery_address' => $addressText,
            'payment_method' => self::extractPaymentMethod($payload),
        ];

        if ($notes !== '') {
            $normalized['notes'] = $notes;
        }

        if ($coords !== null) {
            $normalized['lat'] = $coords['latitude'];
            $normalized['lng'] = $coords['longitude'];
        }

        $deliveryFee = self::extractDeliveryFee($payload);
        if ($deliveryFee !== null) {
            $normalized['delivery_fee'] = $deliveryFee;
        }

        // Restaurant selection is already enforced by IntegrationConnection.restaurant_id in IntegrationWebhookController.
        return $normalized;
    }

    /**
     * Used for status updates on non-created events.
     *
     * @param  array<string,mixed>  $incoming
     */
    public static function extractExternalOrderId(array $incoming): string
    {
        $payload = self::extractPayloadNode($incoming);

        $externalOrderId = trim((string) ($payload['orderCode'] ?? $payload['order_code'] ?? $payload['id'] ?? ''));
        if ($externalOrderId === '') {
            $externalOrderId = trim((string) ($incoming['orderCode'] ?? $incoming['id'] ?? ''));
        }

        return $externalOrderId;
    }

    /**
     * Map TGO eventType into internal OrderStatus if applicable.
     *
     * @param  array<string,mixed>  $incoming
     */
    public static function mapEventToOrderStatus(array $incoming): ?OrderStatus
    {
        $eventType = self::extractEventType($incoming);
        if ($eventType === null || trim($eventType) === '') {
            return null;
        }

        return match (strtolower(trim($eventType))) {
            'shipped' => OrderStatus::OnTheWay,
            'delivered' => OrderStatus::Delivered,
            'cancelled', 'unsupplied' => OrderStatus::Cancelled,
            default => null,
        };
    }

    /**
     * @param  array<string,mixed>  $incoming
     */
    private static function extractEventType(array $incoming): ?string
    {
        $candidates = [
            $incoming['eventType'] ?? null,
            $incoming['event_type'] ?? null,
            $incoming['type'] ?? null,
            $incoming['event'] ?? null,
        ];
        foreach ($candidates as $c) {
            if (is_string($c) && trim($c) !== '') {
                return trim($c);
            }
        }

        return null;
    }

    /**
     * @param  array<string,mixed>  $incoming
     * @return array<string,mixed>
     */
    private static function extractPayloadNode(array $incoming): array
    {
        $p = $incoming['payload'] ?? ($incoming['data'] ?? ($incoming['package'] ?? null));
        return is_array($p) ? $p : $incoming;
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private static function extractCustomerName(array $payload): string
    {
        $customer = $payload['customer'] ?? null;
        if (is_array($customer)) {
            $first = trim((string) ($customer['firstName'] ?? $customer['first_name'] ?? ''));
            $last = trim((string) ($customer['lastName'] ?? $customer['last_name'] ?? ''));
            $name = trim($first.' '.$last);
            if ($name !== '') {
                return $name;
            }
        }

        $addr = $payload['address'] ?? null;
        if (is_array($addr)) {
            $first = trim((string) ($addr['firstName'] ?? $addr['first_name'] ?? ''));
            $last = trim((string) ($addr['lastName'] ?? $addr['last_name'] ?? ''));
            $name = trim($first.' '.$last);
            if ($name !== '') {
                return $name;
            }
        }

        return 'Müşteri';
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private static function extractCustomerPhone(array $payload): string
    {
        $customer = $payload['customer'] ?? null;
        if (is_array($customer)) {
            $phone = trim((string) ($customer['phone'] ?? ''));
            if ($phone !== '') {
                return $phone;
            }
        }

        $addr = $payload['address'] ?? null;
        if (is_array($addr)) {
            $phone = trim((string) ($addr['phone'] ?? ''));
            if ($phone !== '') {
                return $phone;
            }
        }

        return '';
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private static function extractCustomerNote(array $payload): string
    {
        $note = $payload['customerNote'] ?? ($payload['customer_note'] ?? ($payload['note'] ?? null));
        return is_string($note) ? trim($note) : '';
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private static function extractPaymentMethod(array $payload): string
    {
        $payment = $payload['payment'] ?? null;
        if (is_array($payment)) {
            $type = strtoupper(trim((string) ($payment['paymentType'] ?? $payment['payment_type'] ?? '')));
            if ($type !== '') {
                if ($type === 'PAY_WITH_CARD') {
                    return 'online';
                }
                if ($type === 'PAY_ON_DELIVERY') {
                    return 'cash_on_delivery';
                }
            }
        }

        // default safe fallback
        return 'cash_on_delivery';
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private static function extractAddressText(array $payload): string
    {
        $addr = $payload['address'] ?? ($payload['deliveryAddress'] ?? $payload['delivery_address'] ?? null);
        if (! is_array($addr)) {
            return '';
        }

        $parts = [];
        foreach (['address1', 'address2', 'addressDescription', 'neighborhood', 'district', 'city'] as $k) {
            $v = $addr[$k] ?? null;
            if (is_string($v) && trim($v) !== '') {
                $parts[] = trim($v);
            }
        }

        $postal = $addr['postalCode'] ?? null;
        if (is_string($postal) && trim($postal) !== '') {
            $parts[] = trim($postal);
        }

        return trim(implode(', ', $parts));
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array{latitude: float, longitude: float}|null
     */
    private static function extractLatLng(array $payload): ?array
    {
        $addr = $payload['address'] ?? ($payload['deliveryAddress'] ?? $payload['delivery_address'] ?? null);
        if (! is_array($addr)) {
            return null;
        }

        $lat = $addr['latitude'] ?? ($addr['lat'] ?? null);
        $lng = $addr['longitude'] ?? ($addr['lng'] ?? ($addr['lon'] ?? null));

        if ($lat === null || $lng === null) {
            return null;
        }

        $latF = is_numeric($lat) ? (float) $lat : (float) trim((string) $lat);
        $lngF = is_numeric($lng) ? (float) $lng : (float) trim((string) $lng);
        if ($latF === 0.0 || $lngF === 0.0) {
            return null;
        }

        return ['latitude' => $latF, 'longitude' => $lngF];
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<int,array{external_sku: string, quantity: int}>
     */
    private static function extractItems(array $payload): array
    {
        $lines = $payload['lines'] ?? null;
        if (! is_array($lines)) {
            return [];
        }

        $out = [];
        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }
            $items = $line['items'] ?? null;
            if (! is_array($items)) {
                continue;
            }

            foreach ($items as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if (($row['isCancelled'] ?? false) === true) {
                    continue;
                }

                $productId = $row['productId'] ?? ($row['product_id'] ?? null);
                $sku = trim((string) ($productId ?? ''));
                $qty = (int) ($row['quantity'] ?? 0);
                if ($sku === '' || $qty < 1) {
                    continue;
                }
                $out[] = [
                    'external_sku' => $sku,
                    'quantity' => $qty,
                ];
            }
        }

        return $out;
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private static function extractDeliveryFee(array $payload): ?float
    {
        $candidates = [
            $payload['deliveryFee'] ?? null,
            $payload['delivery_fee'] ?? null,
            is_array($payload['fees'] ?? null) ? ($payload['fees']['deliveryFee'] ?? ($payload['fees']['delivery_fee'] ?? null)) : null,
        ];

        foreach ($candidates as $v) {
            if ($v === null) {
                continue;
            }
            if (is_numeric($v)) {
                return (float) $v;
            }
            $s = trim((string) $v);
            if ($s !== '' && is_numeric($s)) {
                return (float) $s;
            }
        }

        return null;
    }
}

