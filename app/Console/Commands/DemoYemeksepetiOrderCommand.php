<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Modules\Integrations\Models\IntegrationConnection;
use App\Modules\Integrations\Services\MarketplaceIngestService;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Address;
use App\Support\DortyolDemoDeliveryAddresses;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Yerel / demo ortamda Yemeksepeti webhook akışına denk sipariş üretir (source=marketplace, provider=yemeksepeti).
 *
 * Önemli: Aynı müşteri + aynı adres metni tekrar kullanılırsa teslim koordinatı da eski kalır;
 * demo için her siparişe farklı adres metni + lat/lng verilir.
 */
class DemoYemeksepetiOrderCommand extends Command
{
    protected $signature = 'kurye:demo-yemeksepeti-order
                            {--restaurant= : Restoran id (boşsa isimde "Konak Sof" geçen ilk kayıt)}
                            {--count=1 : Oluşturulacak sipariş adedi (her biri ayrı harici id)}
                            {--refresh-pending : Konak Sofrası "pending" siparişlerinin teslim adres/koordinatını demo havuzundan günceller (onay bekleyenler)}';

    protected $description = 'Konak Sofrası vb. için Yemeksepeti benzeri pazar yeri siparişi oluşturur / bekleyen adresleri yayar.';

    public function handle(MarketplaceIngestService $ingest): int
    {
        $ridOpt = $this->option('restaurant');
        $restaurant = $ridOpt !== null && $ridOpt !== ''
            ? Restaurant::query()->find((int) $ridOpt)
            : Restaurant::query()->where('name', 'like', '%Konak Sof%')->orderBy('id')->first();

        if ($restaurant === null) {
            $this->error('Restoran bulunamadı. --restaurant=id verin veya seed ile Konak Sofrası ekleyin.');

            return self::FAILURE;
        }

        $this->info("Restoran: #{$restaurant->id} {$restaurant->name}");

        if ($this->option('refresh-pending')) {
            return $this->refreshPendingAddresses($restaurant);
        }

        $firmId = (int) $restaurant->firm_id;

        $conn = IntegrationConnection::query()
            ->where('firm_id', $firmId)
            ->where('restaurant_id', $restaurant->id)
            ->where('provider', 'yemeksepeti')
            ->first();

        if ($conn === null) {
            $token = 'dev-yemeksepeti-'.bin2hex(random_bytes(4));
            IntegrationConnection::query()->create([
                'firm_id' => $firmId,
                'restaurant_id' => $restaurant->id,
                'provider' => 'yemeksepeti',
                'credentials_encrypted' => null,
                'settings_json' => ['webhook_token' => $token],
                'is_active' => true,
            ]);
            $this->info("Yemeksepeti entegrasyon kaydı oluşturuldu. Webhook token: {$token}");
            $this->line('Örnek POST: POST '.url('/api/v1/integrations/yemeksepeti/webhook').' + başlık X-Integration-Token: '.$token);
        }

        $product = Product::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        if ($product === null) {
            $this->error('Bu işletmede aktif ürün yok.');

            return self::FAILURE;
        }

        $baseLat = $restaurant->latitude !== null ? (float) $restaurant->latitude : 36.8477;
        $baseLng = $restaurant->longitude !== null ? (float) $restaurant->longitude : 36.2240;
        $slots = DortyolDemoDeliveryAddresses::slots($baseLat, $baseLng);

        $n = max(1, (int) $this->option('count'));
        $ids = [];
        for ($i = 0; $i < $n; $i++) {
            $externalId = 'ys-demo-'.Str::lower(Str::random(12));
            $slot = $slots[$i % count($slots)];
            $lat = $slot['latitude'];
            $lng = $slot['longitude'];
            $addrText = $slot['address'];

            $order = $ingest->ingestFromPayload($firmId, 'yemeksepeti', [
                'external_order_id' => $externalId,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'customer_name' => 'Yemeksepeti Demo Müşteri',
                'customer_phone' => '05559876543',
                'delivery_address' => $addrText,
                'lat' => $lat,
                'lng' => $lng,
                'payment_method' => 'cash_on_delivery',
                'delivery_fee' => 15,
                'notes' => 'Demo: Yemeksepeti kanalı — test adresi '.($i + 1),
            ], (int) $restaurant->id);
            $ids[] = (int) $order->id;
        }

        $this->info(
            'Sipariş oluşturuldu: '.count($ids)." adet (pending; her biri farklı adres/koordinat). id'ler: ".implode(', ', array_map('strval', $ids))
        );
        $this->comment(
            'Eski pending siparişlerin adreslerini yaymak için: php artisan kurye:demo-yemeksepeti-order --refresh-pending'
        );

        return self::SUCCESS;
    }

    private function refreshPendingAddresses(Restaurant $restaurant): int
    {
        $baseLat = $restaurant->latitude !== null ? (float) $restaurant->latitude : 36.8477;
        $baseLng = $restaurant->longitude !== null ? (float) $restaurant->longitude : 36.2240;
        $slots = DortyolDemoDeliveryAddresses::slots($baseLat, $baseLng);

        $pending = Order::query()
            ->where('firm_id', $restaurant->firm_id)
            ->where('restaurant_id', $restaurant->id)
            ->where('status', OrderStatus::Pending->value)
            ->whereNotNull('delivery_address_id')
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            $this->warn('Güncellenecek pending sipariş yok.');

            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($pending->values() as $i => $order) {
            $slot = $slots[$i % count($slots)];
            $addr = Address::query()->find($order->delivery_address_id);
            if ($addr === null) {
                continue;
            }
            $addr->update([
                'address' => $slot['address'],
                'latitude' => $slot['latitude'],
                'longitude' => $slot['longitude'],
            ]);
            $updated++;
            $this->line("#{$order->id} → {$slot['address']}");
        }

        $this->info("Pending sipariş teslim adresi güncellendi: {$updated} kayıt.");

        return self::SUCCESS;
    }
}
