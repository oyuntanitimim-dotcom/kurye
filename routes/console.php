<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\ProductImage;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Restaurants\Models\RestaurantCategory;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Couriers\Models\CourierLocation;
use App\Modules\Users\Models\Address;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('kurye:move-demo-dortyol {--firm= : Firm ID (optional)} {--orders= : Create N new demo orders (optional, default 12)}', function () {
    $firmId = $this->option('firm') !== null ? (int) $this->option('firm') : null;
    $ordersToCreate = $this->option('orders') !== null ? max(0, (int) $this->option('orders')) : 12;

    $firm = $firmId
        ? \App\Modules\Firms\Models\Firm::query()->find($firmId)
        : \App\Modules\Firms\Models\Firm::query()->orderBy('id')->first();

    if (! $firm) {
        $this->error('Firma bulunamadı. --firm ile ID verebilirsiniz.');
        return 1;
    }

    // Hatay / Dörtyol - use "anchor points" on land and apply small jitter,
    // so we don't end up in sea/mountains.
    $anchorPoints = [
        // Dörtyol center / Çarşı
        ['lat' => 36.8386, 'lng' => 36.2292],
        ['lat' => 36.8405, 'lng' => 36.2348],
        ['lat' => 36.8364, 'lng' => 36.2369],
        // Numune Evler / inner town
        ['lat' => 36.8436, 'lng' => 36.2394],
        ['lat' => 36.8451, 'lng' => 36.2326],
        // Yeşilköy side (still inland)
        ['lat' => 36.8477, 'lng' => 36.2219],
        ['lat' => 36.8503, 'lng' => 36.2264],
        // Özerli / Kuzuculu direction
        ['lat' => 36.8328, 'lng' => 36.2453],
        ['lat' => 36.8299, 'lng' => 36.2521],
        // Payas road / industrial
        ['lat' => 36.8580, 'lng' => 36.2459],
        ['lat' => 36.8546, 'lng' => 36.2382],
    ];

    $hoods = [
        'Numune Evler',
        'Yeşilköy',
        'Özerli',
        'Çaylı',
        'Kuzuculu',
        'Sanayi',
        'Payas yolu',
    ];
    $streets = [
        'Atatürk Cad.',
        'İsmet İnönü Cad.',
        'Hükümet Cad.',
        'Cumhuriyet Cad.',
        'Şehitler Cad.',
        'Çarşı Sk.',
        'Okul Sk.',
        'İstasyon Cad.',
    ];

    $jitter = function (float $lat, float $lng, int $meters = 250): array {
        // Rough conversion: 1 deg lat ≈ 111_320m, 1 deg lng ≈ 111_320m * cos(lat)
        $dLat = ($meters / 111320) * ((mt_rand(-1000, 1000)) / 1000);
        $dLng = ($meters / (111320 * max(0.2, cos(deg2rad($lat))))) * ((mt_rand(-1000, 1000)) / 1000);
        return [$lat + $dLat, $lng + $dLng];
    };

    $pickPoint = function () use ($anchorPoints, $jitter): array {
        $base = $anchorPoints[array_rand($anchorPoints)];
        return $jitter((float) $base['lat'], (float) $base['lng'], mt_rand(180, 420));
    };

    DB::transaction(function () use ($firm, $hoods, $streets, $pickPoint, $ordersToCreate) {
        $firm->update([
            'city' => 'Hatay',
            'district' => 'Dörtyol',
        ]);

        // Move all firm restaurants into Dörtyol with realistic addresses.
        $restaurants = Restaurant::query()->where('firm_id', $firm->id)->get();
        foreach ($restaurants as $idx => $r) {
            [$lat, $lng] = $pickPoint();
            $hood = $hoods[array_rand($hoods)];
            $street = $streets[array_rand($streets)];
            $no = mt_rand(3, 180);
            $r->update([
                'address' => "{$hood} Mah. {$street} No:{$no}, Dörtyol / Hatay",
                'latitude' => $lat,
                'longitude' => $lng,
            ]);
        }

        // Move customer addresses (for this firm) near Dörtyol so orders look real.
        $addrIds = Order::query()
            ->where('firm_id', $firm->id)
            ->whereNotNull('delivery_address_id')
            ->pluck('delivery_address_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        if ($addrIds !== []) {
            $addrs = Address::query()->whereIn('id', $addrIds)->get();
            foreach ($addrs as $a) {
                [$lat, $lng] = $pickPoint();
                $hood = $hoods[array_rand($hoods)];
                $street = $streets[array_rand($streets)];
                $no = mt_rand(1, 220);
                $a->update([
                    'address' => "{$hood} Mah. {$street} No:{$no}, Dörtyol / Hatay",
                    'latitude' => $lat,
                    'longitude' => $lng,
                ]);
            }
        }

        // Set courier live locations around Dörtyol.
        $couriers = Courier::query()->where('firm_id', $firm->id)->where('status', 'active')->get();
        foreach ($couriers as $c) {
            [$lat, $lng] = $pickPoint();
            CourierLocation::query()->updateOrCreate(
                ['courier_id' => $c->id],
                ['latitude' => $lat, 'longitude' => $lng, 'updated_at' => now()]
            );
        }

        // Optionally create a few new demo orders with new Dörtyol addresses for better map density.
        if ($ordersToCreate > 0 && $restaurants->isNotEmpty()) {
            $anyUserId = Order::query()->where('firm_id', $firm->id)->whereNotNull('user_id')->value('user_id');
            for ($i = 0; $i < $ordersToCreate; $i++) {
                [$lat, $lng] = $pickPoint();
                $hood = $hoods[array_rand($hoods)];
                $street = $streets[array_rand($streets)];
                $no = mt_rand(1, 220);
                $addr = Address::query()->create([
                    'user_id' => $anyUserId,
                    'title' => 'Ev',
                    'address' => "{$hood} Mah. {$street} No:{$no}, Dörtyol / Hatay",
                    'latitude' => $lat,
                    'longitude' => $lng,
                ]);

                $rest = $restaurants->random();
                Order::query()->create([
                    'firm_id' => $firm->id,
                    'source' => 'marketplace',
                    'user_id' => $anyUserId,
                    'restaurant_id' => $rest->id,
                    'courier_id' => null,
                    'restaurant_courier_requested_at' => now()->subMinutes(mt_rand(1, 40)),
                    'delivery_address_id' => $addr->id,
                    'status' => \App\Enums\OrderStatus::Ready->value,
                    'total_price' => (float) mt_rand(180, 650),
                    'delivery_fee' => 15.00,
                    'discount_amount' => 0,
                    'payment_method' => 'cash_on_delivery',
                ]);
            }
        }
    });

    $this->info("Tamam: Firma {$firm->id} Hatay/Dörtyol'a taşındı. Restoran/adres/kurye konumları güncellendi.");
    $this->info("Komut: kurye:move-demo-dortyol --firm={$firm->id}");
    return 0;
})->purpose('Move demo firm data into Hatay/Dörtyol for realistic map view');

Artisan::command('kurye:seed-konak-menu {--reset : Delete existing categories/products for Konak Sofrası Lokantası}', function () {
    $restaurant = Restaurant::query()
        ->where('name', 'like', '%Konak Sofra%')
        ->orWhere('name', 'like', '%Konak Sofrası%')
        ->first();

    if (! $restaurant) {
        $this->error('Konak Sofrası restoranı bulunamadı. (DB seed çalıştı mı?)');
        return 1;
    }

    if ((bool) $this->option('reset')) {
        ProductImage::query()->whereIn('product_id', Product::query()->where('restaurant_id', $restaurant->id)->select('id'))->delete();
        Product::query()->where('restaurant_id', $restaurant->id)->delete();
        RestaurantCategory::query()->where('restaurant_id', $restaurant->id)->delete();
        $this->warn('Reset yapıldı: ürün/kategori silindi.');
    }

    $img = fn (string $file) => 'https://commons.wikimedia.org/wiki/Special:FilePath/'.rawurlencode($file);

    $menu = [
        'Çorbalar' => [
            ['Mercimek Çorbası', 75, 'Limon, kıtır ekmek ile.', [$img('Mercimek çorbası.jpg')]],
            ['Ezogelin Çorbası', 80, 'Acı sevenlere.', [$img('Ezogelin corbasi.jpg')]],
            ['Tavuk Suyu Çorba', 90, 'Ev usulü.', [$img('Chicken soup.jpg')]],
        ],
        'Kahvaltı' => [
            ['Serpme Kahvaltı (2 kişilik)', 620, 'Peynir çeşitleri, zeytin, bal-kaymak, menemen, sınırsız çay.', [$img('Turkish breakfast.jpg')]],
            ['Menemen', 180, 'Domates, biber, yumurta.', [$img('Menemen.jpg')]],
            ['Sucuklu Yumurta', 210, 'Dana sucuk ile.', [$img('Sucuklu yumurta.jpg')]],
            ['Kaşarlı Tost', 160, 'Kaşar peyniri.', [$img('Cheese toast.jpg')]],
        ],
        'Mezeler' => [
            ['Haydari', 110, 'Süzme yoğurt, nane, zeytinyağı.', [$img('Haydari.jpg')]],
            ['Acılı Ezme', 115, 'Domates, biber, baharat.', [$img('Acılı ezme.jpg')]],
            ['Humus', 130, 'Nohut, tahin, zeytinyağı.', [$img('Hummus.jpg')]],
        ],
        'Salatalar' => [
            ['Çoban Salata', 120, 'Domates, salatalık, soğan.', [$img('Coban salatasi.jpg')]],
            ['Mevsim Salata', 130, 'Yeşillikler, limon sos.', [$img('Mixed salad.jpg')]],
            ['Tavuklu Sezar', 240, 'Izgara tavuk, parmesan, kruton.', [$img('Caesar salad.jpg')]],
        ],
        'Izgara & Kebap' => [
            ['Adana Kebap', 520, 'Közlenmiş biber-domates, lavaş.', [$img('Adana kebab.jpg')]],
            ['Urfa Kebap', 520, 'Acısız.', [$img('Urfa kebab.jpg')]],
            ['Tavuk Şiş', 420, 'Izgara tavuk şiş.', [$img('Chicken shish kebab.jpg')]],
            ['Köfte', 390, 'Izgara köfte, patates.', [$img('Kofte.jpg')]],
        ],
        'Ev Yemekleri' => [
            ['Kuru Fasulye', 260, 'Pilav ile.', [$img('Kuru fasulye.jpg')]],
            ['İzmir Köfte', 320, 'Fırında patates ile.', [$img('Izmir kofte.jpg')]],
            ['Tavuk Sote', 310, 'Sebzeli.', [$img('Chicken saute.jpg')]],
            ['Et Sote', 380, 'Dana eti.', [$img('Beef saute.jpg')]],
        ],
        'Pide & Lahmacun' => [
            ['Karışık Pide', 260, 'Kıyma, kaşar, biber.', [$img('Kavurma ve peynirli pide.jpg'), $img('Türk pidesi.jpg')]],
            ['Kaşarlı Pide', 240, 'Bol kaşar.', [$img('Peynirli pide.jpg')]],
            ['Kıymalı Pide', 250, 'Baharatlı kıyma.', [$img('Kıymalı pide.jpg')]],
            ['Lahmacun', 70, 'Limon ile.', [$img('Lahmacun.jpg'), $img('Acılı Lahmacun.jpg')]],
        ],
        'Tatlılar' => [
            ['Sütlaç', 150, 'Fırın sütlaç.', [$img('Rice pudding.jpg')]],
            ['Künefe', 260, 'Sıcak servis.', [$img('Künefe.jpg')]],
            ['Baklava', 220, 'Antep fıstıklı.', [$img('Baklava.jpg')]],
        ],
        'İçecekler' => [
            ['Ayran', 25, null, [$img('Ayran.jpg')]],
            ['Şalgam', 35, 'Acılı / acısız.', [$img('Salgam.jpg')]],
            ['Kola (330ml)', 60, null, []],
            ['Soda', 35, null, []],
            ['Çay', 25, null, [$img('Turkish tea.jpg')]],
            ['Filtre Kahve', 120, null, [$img('Filter coffee.jpg')]],
        ],
    ];

    $categoryOrder = 1;
    $createdProducts = 0;
    foreach ($menu as $categoryName => $items) {
        $category = RestaurantCategory::query()->firstOrCreate(
            ['restaurant_id' => $restaurant->id, 'name' => $categoryName],
            ['sort_order' => $categoryOrder]
        );
        $category->update(['sort_order' => $categoryOrder]);
        $categoryOrder++;

        foreach ($items as [$name, $price, $desc, $images]) {
            $product = Product::query()->firstOrNew([
                'restaurant_id' => $restaurant->id,
                'name' => $name,
            ]);
            $product->fill([
                'category_id' => $category->id,
                'description' => $desc,
                'price' => (float) $price,
                'discounted_price' => null,
                'status' => 'active',
                'stock' => 999,
                'prep_time_minutes' => 10,
            ]);
            $product->save();

            if (is_array($images) && count($images) > 0) {
                // If product has no images yet, seed a small gallery.
                if (! $product->images()->exists()) {
                    $sort = 0;
                    foreach ($images as $idx => $url) {
                        if (! $url) continue;
                        $product->images()->create([
                            'path' => $url,
                            'is_primary' => $idx === 0,
                            'sort_order' => $sort++,
                        ]);
                    }
                }
            }

            $createdProducts++;
        }
    }

    $this->info('Konak Sofrası menüsü eklendi/güncellendi. Ürün: '.$createdProducts);
    $this->info('Not: Görseller açık lisanslı kaynaklardan URL olarak bağlandı.');
    return 0;
})->purpose('Seed Konak Sofrası with realistic menu + images');

Artisan::command('kurye:fix-konak-images {--dry-run : Only report, do not change DB}', function () {
    $restaurant = Restaurant::query()
        ->where('name', 'like', '%Konak Sofra%')
        ->orWhere('name', 'like', '%Konak Sofrası%')
        ->first();

    if (! $restaurant) {
        $this->error('Konak Sofrası restoranı bulunamadı.');
        return 1;
    }

    $img = fn (string $file) => 'https://commons.wikimedia.org/wiki/Special:FilePath/'.rawurlencode($file);

    $fallbacks = [
        'Çorbalar' => $img('Mercimek çorbası.jpg'),
        'Kahvaltı' => $img('Turkish breakfast.jpg'),
        'Mezeler' => $img('Hummus.jpg'),
        'Salatalar' => $img('Mevsim salata.jpg'),
        'Izgara & Kebap' => $img('Adana_kebab.jpg'),
        'Ev Yemekleri' => $img('Kofte.jpg'),
        'Pide & Lahmacun' => $img('Türk pidesi.jpg'),
        'Pide & Pizza' => $img('Türk pidesi.jpg'),
        'Tatlılar' => $img('Baklava.jpg'),
        'İçecekler' => $img('Turkish_tea_with_sugar_and_spoon.jpg'),
        'İçecek' => $img('Turkish_tea_with_sugar_and_spoon.jpg'),
    ];

    $dry = (bool) $this->option('dry-run');
    $products = Product::query()
        ->where('restaurant_id', $restaurant->id)
        ->with(['category', 'images'])
        ->get();

    $missing = 0;
    $fixedLegacy = 0;
    $added = 0;
    $brokenUrl = 0;
    $reportedEmpty = 0;
    $checked = 0;

    $isOkUrl = function (string $url) use (&$checked): bool {
        $checked++;
        try {
            $res = Http::timeout(6)
                ->withOptions(['allow_redirects' => true])
                ->head($url);
            $st = $res->status();
            return $st >= 200 && $st < 400;
        } catch (\Throwable) {
            return false;
        }
    };

    foreach ($products as $p) {
        $catName = (string) ($p->category?->name ?? '');
        $fallback = $fallbacks[$catName] ?? $img('Cuisine_of_Turkey'); // safe fallback

        // If legacy single image exists but gallery is empty, migrate it to gallery.
        if (($p->images === null || $p->images->isEmpty()) && $p->image) {
            $missing++;
            $this->line("LEGACY_ONLY | {$p->id} | {$p->name} | {$catName}");
            if (! $dry) {
                $p->images()->create([
                    'path' => (string) $p->image,
                    'is_primary' => true,
                    'sort_order' => 0,
                ]);
                $fixedLegacy++;
                $added++;
            }
            // reload for checks below
            $p->load('images');
        }

        // If product has no gallery images, seed one primary image.
        if (! $p->images || $p->images->isEmpty()) {
            $missing++;
            $this->line("NO_IMAGE | {$p->id} | {$p->name} | {$catName}");
            if (! $dry) {
                $p->images()->create([
                    'path' => $fallback,
                    'is_primary' => true,
                    'sort_order' => 0,
                ]);
                $added++;
            }
            continue;
        }

        // If primary image has empty path, replace it.
        $primary = $p->images->firstWhere('is_primary', true) ?? $p->images->first();
        $path = (string) ($primary->path ?? '');
        if ($path === '') {
            $reportedEmpty++;
            $this->line("EMPTY_PRIMARY | {$p->id} | {$p->name} | {$catName}");
            if (! $dry) {
                $p->images()->update(['is_primary' => false]);
                $primary->update(['path' => $fallback, 'is_primary' => true]);
                $added++;
            }
            continue;
        }

        // If primary URL is broken (404/timeout), replace with fallback.
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            if (! $isOkUrl($path)) {
                $brokenUrl++;
                $this->line("BROKEN_URL | {$p->id} | {$p->name} | {$catName} | {$path}");
                if (! $dry) {
                    $p->images()->update(['is_primary' => false]);
                    $primary->update(['path' => $fallback, 'is_primary' => true]);
                    $added++;
                }
            }
        }
    }

    $this->info("Taranan ürün: {$products->count()}");
    $this->info("Görseli olmayan: {$missing}");
    $this->info("Legacy->gallery aktarılan: {$fixedLegacy}");
    $this->info("Boş primary raporu: {$reportedEmpty}");
    $this->info("Bozuk URL: {$brokenUrl}");
    $this->info("Kontrol edilen URL: {$checked}");
    $this->info($dry ? 'DRY RUN: değişiklik yapılmadı.' : "Eklenen/düzeltlenen: {$added}");
    return 0;
})->purpose('Fix Konak Sofrası products missing images (fallback by category)');

Artisan::command('kurye:normalize-konak-images {--reset : Replace all product gallery images with category fallback}', function () {
    $restaurant = Restaurant::query()
        ->where('name', 'like', '%Konak Sofra%')
        ->orWhere('name', 'like', '%Konak Sofrası%')
        ->first();

    if (! $restaurant) {
        $this->error('Konak Sofrası restoranı bulunamadı.');
        return 1;
    }

    $img = fn (string $file) => 'https://commons.wikimedia.org/wiki/Special:FilePath/'.rawurlencode($file);

    // Verified / high-confidence Commons titles
    $fallbacks = [
        'Çorbalar' => $img('Mercimek çorbası.jpg'),
        'Kahvaltı' => $img('Sucuklu yumurta, Turkish sunday breakfast.jpg'),
        'Mezeler' => $img('Hummus.jpg'),
        'Salatalar' => $img('Mevsim salata.jpg'),
        'Izgara & Kebap' => $img('Adana_kebab.jpg'),
        'Ev Yemekleri' => $img('Koefte.jpg'),
        'Pide & Lahmacun' => $img('Türk pidesi.jpg'),
        'Pide & Pizza' => $img('Türk pidesi.jpg'),
        'Tatlılar' => $img('Baklava.jpg'),
        'İçecekler' => $img('Turkish tea with sugar and spoon.jpg'),
        'İçecek' => $img('Turkish tea with sugar and spoon.jpg'),
    ];

    $reset = (bool) $this->option('reset');

    $products = Product::query()
        ->where('restaurant_id', $restaurant->id)
        ->with('category')
        ->get();

    if ($reset) {
        ProductImage::query()
            ->whereIn('product_id', $products->pluck('id')->all())
            ->delete();
        $this->warn('Reset: Konak ürün galerileri temizlendi.');
    }

    $updated = 0;
    foreach ($products as $p) {
        $catName = (string) ($p->category?->name ?? '');
        $fallback = $fallbacks[$catName] ?? $img('Türk pidesi.jpg');

        $primary = $p->images()->firstWhere('is_primary', true) ?? $p->images()->first();
        if ($primary) {
            $p->images()->update(['is_primary' => false]);
            $primary->update(['path' => $fallback, 'is_primary' => true, 'sort_order' => 0]);
        } else {
            $p->images()->create(['path' => $fallback, 'is_primary' => true, 'sort_order' => 0]);
        }

        $updated++;
    }

    $this->info("Normalize tamamlandı. Ürün: {$updated}");
    $this->info('Not: Bazı ürünler aynı kategori görselini paylaşır (garantili görünürlük için).');
    return 0;
})->purpose('Normalize Konak product images to verified category fallbacks');
