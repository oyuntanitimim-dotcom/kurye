<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Services;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Modules\Integrations\Models\IntegrationExternalOrder;
use App\Modules\Integrations\Models\IntegrationProductMap;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Models\OrderStatusHistory;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Address;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use App\Services\Geocoding\NominatimGeocoder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MarketplaceIngestService
{
    public function __construct(
        private readonly NominatimGeocoder $nominatimGeocoder
    ) {}

    /**
     * Normalize gövde: restoran ürünleri üzerinden iç sipariş oluşturur (idempotent).
     * Kalem: product_id veya external_sku (işletmede tanımlı eşleme + provider).
     *
     * @param  array<string, mixed>  $payload
     * @param  int|null  $restaurantIdFromConnection  Bağlantı kaydındaki işletme; set ise gövdedeki restaurant_id yok sayılır.
     */
    public function ingestFromPayload(int $firmId, string $provider, array $payload, ?int $restaurantIdFromConnection = null): Order
    {
        $payload = $this->normalizePayload($payload);

        $externalId = trim((string) ($payload['external_order_id'] ?? ''));
        if ($externalId === '') {
            $externalId = trim((string) ($payload['order_id'] ?? $payload['id'] ?? ''));
        }
        if ($externalId === '') {
            throw ValidationException::withMessages(['external_order_id' => 'Zorunlu.']);
        }

        $existing = IntegrationExternalOrder::query()
            ->where('firm_id', $firmId)
            ->where('provider', $provider)
            ->where('external_order_id', $externalId)
            ->first();

        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        if ($existing !== null && $existing->order_id !== null) {
            return Order::query()->findOrFail($existing->order_id);
        }

        $restaurantId = $restaurantIdFromConnection ?? (int) ($payload['restaurant_id'] ?? 0);
        if ($restaurantId < 1) {
            throw ValidationException::withMessages(['restaurant_id' => 'Zorunlu veya geçersiz.']);
        }

        $restaurant = Restaurant::query()->where('firm_id', $firmId)->whereKey($restaurantId)->firstOrFail();

        $lines = $this->extractItems($payload);
        if (! is_array($lines) || $lines === []) {
            throw ValidationException::withMessages(['items' => 'En az bir kalem gerekli.']);
        }

        $total = 0.0;
        $built = [];
        foreach ($lines as $row) {
            if (! is_array($row)) {
                continue;
            }
            $qty = (int) ($row['quantity'] ?? 0);
            if ($qty < 1) {
                continue;
            }
            $pid = (int) ($row['product_id'] ?? 0);
            $extSku = trim((string) ($row['external_sku'] ?? $row['sku'] ?? $row['barcode'] ?? $row['product_code'] ?? ''));
            if ($pid < 1 && $extSku !== '') {
                $mappedId = IntegrationProductMap::query()
                    ->where('restaurant_id', $restaurant->id)
                    ->where('provider', $provider)
                    ->where('external_sku', $extSku)
                    ->value('product_id');
                if ($mappedId === null) {
                    throw ValidationException::withMessages(['items' => 'Eşlenmemiş ürün kodu: '.$extSku]);
                }
                $pid = (int) $mappedId;
            }
            if ($pid < 1) {
                continue;
            }
            $product = Product::query()
                ->where('restaurant_id', $restaurant->id)
                ->where('status', 'active')
                ->whereKey($pid)
                ->first();
            if ($product === null) {
                throw ValidationException::withMessages(['items' => 'Geçersiz ürün: '.$pid]);
            }
            $lineTotal = $product->effectiveUnitPrice() * $qty;
            $total += $lineTotal;
            $built[] = [$product, $qty];
        }

        if ($built === []) {
            throw ValidationException::withMessages(['items' => 'Geçerli kalem yok.']);
        }

        $deliveryFee = (float) ($payload['delivery_fee'] ?? 0);

        return DB::transaction(function () use ($firmId, $provider, $externalId, $hash, $existing, $restaurant, $built, $total, $deliveryFee, $payload): Order {
            [$customerId, $deliveryAddressId] = $this->ensureCustomerAndDeliveryAddress($firmId, $provider, $externalId, $payload);

            $order = Order::query()->create([
                'firm_id' => $firmId,
                'source' => OrderSource::Marketplace->value,
                'marketplace_provider' => $provider,
                'user_id' => $customerId,
                'restaurant_id' => $restaurant->id,
                'courier_id' => null,
                'delivery_address_id' => $deliveryAddressId,
                'status' => OrderStatus::Pending->value,
                'total_price' => $total + $deliveryFee,
                'delivery_fee' => $deliveryFee,
                'discount_amount' => 0,
                'payment_method' => (string) ($payload['payment_method'] ?? 'cash_on_delivery'),
                'notes' => isset($payload['notes']) ? (string) $payload['notes'] : null,
                'customer_name' => (string) ($payload['customer_name'] ?? ''),
                'customer_phone' => (string) ($payload['customer_phone'] ?? ''),
            ]);

            foreach ($built as [$product, $qty]) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'price' => $product->effectiveUnitPrice(),
                    'quantity' => $qty,
                    'product_name' => $product->name,
                ]);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => OrderStatus::Pending->value,
                'meta' => ['source' => 'marketplace_ingest', 'provider' => $provider],
                'created_at' => now(),
            ]);

            if ($existing !== null) {
                $existing->update([
                    'order_id' => $order->id,
                    'last_payload_hash' => $hash,
                    'status' => 'order_created',
                    'error_message' => null,
                ]);
            } else {
                IntegrationExternalOrder::query()->create([
                    'firm_id' => $firmId,
                    'provider' => $provider,
                    'external_order_id' => $externalId,
                    'order_id' => $order->id,
                    'last_payload_hash' => $hash,
                    'status' => 'order_created',
                    'error_message' => null,
                ]);
            }

            return $order->fresh();
        });
    }

    /**
     * Marketplace siparişi için: müşteri user + teslimat adresi üretir (idempotent/tekrar kullanılabilir).
     *
     * @param  array<string, mixed>  $payload
     * @return array{0: int|null, 1: int|null} [customer_user_id, delivery_address_id]
     */
    private function ensureCustomerAndDeliveryAddress(int $firmId, string $provider, string $externalId, array $payload): array
    {
        $addrText = $this->extractAddressText($payload);
        $phone = trim((string) ($payload['customer_phone'] ?? ''));
        $name = trim((string) ($payload['customer_name'] ?? ''));

        if ($addrText === '' && $phone === '' && $name === '') {
            return [null, null];
        }

        $customerRoleId = (int) (Role::query()->where('name', Role::CUSTOMER)->value('id') ?? 0);
        if ($customerRoleId <= 0) {
            // Rol yoksa adres bağlamı kuramayız; sipariş yine oluşsun.
            return [null, null];
        }

        $user = null;
        if ($phone !== '') {
            $user = User::query()
                ->where('firm_id', $firmId)
                ->where('role_id', $customerRoleId)
                ->where('phone', $phone)
                ->orderBy('id')
                ->first();
        }

        if ($user === null) {
            $email = 'mkt_'.$provider.'_'.Str::lower(Str::random(10)).'@marketplace.local';
            $user = User::query()->create([
                'firm_id' => $firmId,
                'role_id' => $customerRoleId,
                'restaurant_id' => null,
                'name' => $name !== '' ? $name : 'Müşteri',
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'password' => Str::random(32),
                'status' => 'active',
            ]);
        }

        if ($addrText === '') {
            return [$user->id, null];
        }

        // Aynı kullanıcı + aynı adres metni varsa tekrar kullan.
        $existingAddr = Address::query()
            ->where('user_id', $user->id)
            ->where('address', $addrText)
            ->orderByDesc('id')
            ->first();

        if ($existingAddr !== null && $existingAddr->latitude !== null && $existingAddr->longitude !== null) {
            return [$user->id, (int) $existingAddr->id];
        }

        $coords = $this->extractLatLng($payload);
        if ($coords === null) {
            $coords = $this->nominatimGeocoder->geocodeFreeText($addrText);
        }

        $row = [
            'user_id' => $user->id,
            'title' => 'Teslimat',
            'address' => $addrText,
        ];
        if ($coords !== null) {
            $row['latitude'] = $coords['latitude'];
            $row['longitude'] = $coords['longitude'];
        }

        if ($existingAddr !== null) {
            $existingAddr->update($row);
            return [$user->id, (int) $existingAddr->id];
        }

        $addr = Address::query()->create($row);

        return [$user->id, (int) $addr->id];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractAddressText(array $payload): string
    {
        $candidates = [
            $payload['delivery_address'] ?? null,
            $payload['deliveryAddress'] ?? null,
            $payload['address'] ?? null,
            $payload['delivery']['address'] ?? null,
            $payload['delivery']['full_address'] ?? null,
            $payload['customer_address'] ?? null,
            $payload['customerAddress'] ?? null,
            $payload['delivery']['delivery_address'] ?? null,
            $payload['delivery']['deliveryAddress'] ?? null,
        ];
        foreach ($candidates as $c) {
            if (is_string($c) && trim($c) !== '') {
                return trim($c);
            }
            if (is_array($c)) {
                // bazen {address: "..."} veya {full: "..."}
                $a = $c['address'] ?? ($c['full'] ?? ($c['full_address'] ?? null));
                if ($a === null) {
                    $a = $c['fullAddress'] ?? ($c['deliveryAddress'] ?? null);
                }
                if (is_string($a) && trim($a) !== '') {
                    return trim($a);
                }
            }
        }
        return '';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{latitude: float, longitude: float}|null
     */
    private function extractLatLng(array $payload): ?array
    {
        $latCandidates = [
            $payload['delivery_latitude'] ?? null,
            $payload['latitude'] ?? null,
            $payload['lat'] ?? null,
            $payload['deliveryLatitude'] ?? null,
            $payload['delivery']['latitude'] ?? null,
            $payload['delivery']['lat'] ?? null,
            $payload['delivery']['delivery_latitude'] ?? null,
            $payload['delivery']['deliveryLatitude'] ?? null,
            is_array($payload['delivery_address'] ?? null) ? (($payload['delivery_address']['latitude'] ?? null) ?? ($payload['delivery_address']['lat'] ?? null)) : null,
            is_array($payload['deliveryAddress'] ?? null) ? (($payload['deliveryAddress']['latitude'] ?? null) ?? ($payload['deliveryAddress']['lat'] ?? null)) : null,
            is_array($payload['coordinates'] ?? null) ? (($payload['coordinates']['latitude'] ?? null) ?? ($payload['coordinates']['lat'] ?? null)) : null,
            is_array($payload['delivery'] ?? null) && is_array($payload['delivery']['coordinates'] ?? null)
                ? (($payload['delivery']['coordinates']['latitude'] ?? null) ?? ($payload['delivery']['coordinates']['lat'] ?? null))
                : null,
        ];
        $lngCandidates = [
            $payload['delivery_longitude'] ?? null,
            $payload['longitude'] ?? null,
            $payload['lng'] ?? null,
            $payload['lon'] ?? null,
            $payload['deliveryLongitude'] ?? null,
            $payload['delivery']['longitude'] ?? null,
            $payload['delivery']['lng'] ?? null,
            $payload['delivery']['lon'] ?? null,
            $payload['delivery']['delivery_longitude'] ?? null,
            $payload['delivery']['deliveryLongitude'] ?? null,
            is_array($payload['delivery_address'] ?? null) ? (($payload['delivery_address']['longitude'] ?? null) ?? ($payload['delivery_address']['lng'] ?? null) ?? ($payload['delivery_address']['lon'] ?? null)) : null,
            is_array($payload['deliveryAddress'] ?? null) ? (($payload['deliveryAddress']['longitude'] ?? null) ?? ($payload['deliveryAddress']['lng'] ?? null) ?? ($payload['deliveryAddress']['lon'] ?? null)) : null,
            is_array($payload['coordinates'] ?? null) ? (($payload['coordinates']['longitude'] ?? null) ?? ($payload['coordinates']['lng'] ?? null) ?? ($payload['coordinates']['lon'] ?? null)) : null,
            is_array($payload['delivery'] ?? null) && is_array($payload['delivery']['coordinates'] ?? null)
                ? (($payload['delivery']['coordinates']['longitude'] ?? null) ?? ($payload['delivery']['coordinates']['lng'] ?? null) ?? ($payload['delivery']['coordinates']['lon'] ?? null))
                : null,
        ];

        $lat = null;
        foreach ($latCandidates as $v) {
            if ($v === null) {
                continue;
            }
            $x = is_numeric($v) ? (float) $v : (float) trim((string) $v);
            if ($x !== 0.0) {
                $lat = $x;
                break;
            }
        }

        $lng = null;
        foreach ($lngCandidates as $v) {
            if ($v === null) {
                continue;
            }
            $x = is_numeric($v) ? (float) $v : (float) trim((string) $v);
            if ($x !== 0.0) {
                $lng = $x;
                break;
            }
        }

        if ($lat === null || $lng === null) {
            return null;
        }

        return ['latitude' => $lat, 'longitude' => $lng];
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function normalizePayload(array $payload): array
    {
        // Bazi pazar yerleri veriyi {order:{...}} gibi nested gonderir.
        $orderNode = $payload['order'] ?? null;
        if (is_array($orderNode)) {
            return array_merge($orderNode, $payload);
        }

        return $payload;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<int,mixed>
     */
    private function extractItems(array $payload): array
    {
        $candidates = [
            $payload['items'] ?? null,
            $payload['line_items'] ?? null,
            $payload['lines'] ?? null,
            $payload['products'] ?? null,
            is_array($payload['order'] ?? null) ? ($payload['order']['items'] ?? null) : null,
            is_array($payload['order'] ?? null) ? ($payload['order']['line_items'] ?? null) : null,
        ];
        foreach ($candidates as $c) {
            if (is_array($c) && $c !== []) {
                return array_values($c);
            }
        }

        return [];
    }
}
