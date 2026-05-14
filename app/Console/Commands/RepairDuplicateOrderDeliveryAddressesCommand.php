<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Address;
use App\Support\DortyolDemoDeliveryAddresses;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Aynı teslimat adresi kaydı veya aynı metin+koordinat birden fazla siparişte tekrar ettiğinde
 * adresleri Dörtyol demo havuzundan **global sırayla** benzersiz atar (slot tekrarı olmaması için).
 */
class RepairDuplicateOrderDeliveryAddressesCommand extends Command
{
    protected $signature = 'kurye:repair-duplicate-order-delivery-addresses
                            {--restaurant= : Restoran id (boşsa isimde "Konak Sof" geçen ilk kayıt)}
                            {--dry-run : Değişiklik yapmadan listele}';

    protected $description = 'Çakışan teslimat adreslerini ayırır (tüm havuz sırayla kullanılır).';

    public function handle(): int
    {
        $ridOpt = $this->option('restaurant');
        $restaurant = $ridOpt !== null && $ridOpt !== ''
            ? Restaurant::query()->find((int) $ridOpt)
            : Restaurant::query()->where('name', 'like', '%Konak Sof%')->orderBy('id')->first();

        if ($restaurant === null) {
            $this->error('Restoran bulunamadı. --restaurant=id verin.');

            return self::FAILURE;
        }

        $dry = $this->option('dry-run');
        $baseLat = $restaurant->latitude !== null ? (float) $restaurant->latitude : 36.8477;
        $baseLng = $restaurant->longitude !== null ? (float) $restaurant->longitude : 36.2240;
        $slots = DortyolDemoDeliveryAddresses::slots($baseLat, $baseLng);
        $slotCount = count($slots);

        $this->info("Restoran: #{$restaurant->id} {$restaurant->name}");

        $fixes = 0;
        /** Sonraki slot indexi (çakışma grupları arasında taşınır — aynı 3 adresi döngüsel tekrar etmez) */
        $slotCursor = 0;

        $nextSlot = static function () use (&$slotCursor, $slots, $slotCount): array {
            $s = $slots[$slotCursor % $slotCount];
            ++$slotCursor;

            return $s;
        };

        /** Havuz 14 nokta; çok siparişte aynı slota düşmemek için ~±4 m benzersiz kayma */
        $jitter = static function (int $orderId, float $lat, float $lng): array {
            $dLat = (($orderId * 7919) % 97 - 48) * 1.1e-5;
            $dLng = (($orderId * 6421) % 89 - 44) * 1.1e-5;

            return [$lat + $dLat, $lng + $dLng];
        };

        DB::transaction(function () use ($restaurant, $dry, &$fixes, $nextSlot, $jitter): void {
            $withAddr = Order::query()
                ->where('restaurant_id', $restaurant->id)
                ->whereNotNull('delivery_address_id')
                ->with('deliveryAddress')
                ->orderBy('id')
                ->get();

            /** @var array<string, list<Order>> $byKey */
            $byKey = [];
            foreach ($withAddr as $order) {
                $addr = $order->deliveryAddress;
                if ($addr === null || $addr->latitude === null || $addr->longitude === null || trim((string) $addr->address) === '') {
                    continue;
                }
                $key = strtolower(trim((string) $addr->address)).'|'
                    .sprintf('%.5f', (float) $addr->latitude).'|'.sprintf('%.5f', (float) $addr->longitude);
                $byKey[$key][] = $order;
            }

            foreach ($byKey as $key => $group) {
                if (count($group) <= 1) {
                    continue;
                }
                $groupList = array_values($group);
                $uniqAddrIds = collect($groupList)->map(fn (Order $o) => (int) $o->delivery_address_id)->unique();

                if ($uniqAddrIds->count() > 1) {
                    $this->comment('Farklı adres id, aynı içerik: '.mb_substr((string) $key, 0, 72).'…');
                    foreach ($groupList as $order) {
                        $slot = $nextSlot();
                        $addr = $order->deliveryAddress;
                        if ($addr === null) {
                            continue;
                        }
                        [$plat, $plng] = $jitter((int) $order->id, (float) $slot['latitude'], (float) $slot['longitude']);
                        $this->line(sprintf('  #%d adres #%d → %s', (int) $order->id, (int) $addr->id, $slot['address']));
                        if (! $dry) {
                            $addr->update([
                                'address' => $slot['address'],
                                'latitude' => $plat,
                                'longitude' => $plng,
                            ]);
                        }
                        ++$fixes;
                    }

                    continue;
                }

                /** Tek delivery_address_id — paylaşılan kayıt; ilk sipariş kaydı kalır, diğerleri klon */
                $addressId = (int) $uniqAddrIds->first();
                $base = Address::query()->find($addressId);
                if ($base === null) {
                    continue;
                }
                $this->comment('Paylaşılan adres #'.$addressId.' — '.count($groupList).' sipariş');
                foreach ($groupList as $i => $order) {
                    $slot = $nextSlot();
                    [$plat, $plng] = $jitter((int) $order->id, (float) $slot['latitude'], (float) $slot['longitude']);
                    $this->line(sprintf('  #%d → %s', (int) $order->id, $slot['address']));
                    if ($i === 0) {
                        if (! $dry) {
                            $base->update([
                                'address' => $slot['address'],
                                'latitude' => $plat,
                                'longitude' => $plng,
                            ]);
                        }
                    } elseif (! $dry) {
                        $clone = Address::query()->create([
                            'user_id' => $base->user_id,
                            'title' => $base->title ?: 'Teslimat',
                            'address' => $slot['address'],
                            'latitude' => $plat,
                            'longitude' => $plng,
                        ]);
                        $order->update(['delivery_address_id' => $clone->id]);
                    }
                    ++$fixes;
                }
            }
        });

        if ($fixes === 0) {
            $this->comment('Ayırılacak yinelenen teslim adresi bulunmadı.');
        } else {
            $this->info($dry ? 'Dry-run: yukarıdaki adresler atanacaktı.' : "Tamam: {$fixes} adres güncellemesi yapıldı.");
        }

        return self::SUCCESS;
    }
}
