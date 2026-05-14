<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\Address;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillOrderDeliveryAddressesCommand extends Command
{
    protected $signature = 'kurye:backfill-order-addresses
        {--firm_domain=localhost : Firma domain (default: localhost)}
        {--limit=300 : En fazla kaç sipariş güncellensin}
        {--active-only : Sadece teslim/iptal hariç siparişler}
        {--dry-run : Veritabanına yazmadan simülasyon}';

    protected $description = 'delivery_address_id boş siparişlere (yakın konumla) teslimat adresi ekler.';

    public function handle(): int
    {
        $firmDomain = (string) $this->option('firm_domain');
        $limit = max(1, (int) $this->option('limit'));
        $activeOnly = (bool) $this->option('active-only');
        $dryRun = (bool) $this->option('dry-run');

        $firm = Firm::query()->where('domain', $firmDomain)->first()
            ?? Firm::query()->orderBy('id')->first();
        if (! $firm) {
            $this->error('Firma bulunamadı.');
            return self::FAILURE;
        }

        $customerRoleId = (int) (Role::query()->where('name', Role::CUSTOMER)->value('id') ?? 0);
        if ($customerRoleId <= 0) {
            $this->error('Customer rolü yok.');
            return self::FAILURE;
        }

        $q = Order::query()
            ->where('firm_id', $firm->id)
            ->whereNull('delivery_address_id')
            ->with('restaurant');

        if ($activeOnly) {
            $q->whereNotIn('status', [OrderStatus::Delivered->value, OrderStatus::Cancelled->value]);
        }

        $orders = $q->orderBy('id')->limit($limit)->get();
        $this->info("Firma: #{$firm->id} {$firm->name} — hedef sipariş: {$orders->count()} (limit={$limit})");
        if ($orders->isEmpty()) {
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY RUN: yazılmayacak.');
        }

        $createdUsers = 0;
        $createdAddrs = 0;
        $updatedOrders = 0;

        DB::beginTransaction();
        try {
            foreach ($orders as $o) {
                $phone = trim((string) ($o->customer_phone ?? ''));
                $name = trim((string) ($o->customer_name ?? ''));

                $user = null;
                if ($o->user_id !== null) {
                    $user = User::query()->find((int) $o->user_id);
                }
                if ($user === null && $phone !== '') {
                    $user = User::query()
                        ->where('firm_id', $firm->id)
                        ->where('role_id', $customerRoleId)
                        ->where('phone', $phone)
                        ->orderBy('id')
                        ->first();
                }
                if ($user === null) {
                    $email = 'backfill_'.Str::lower(Str::random(10)).'@local.test';
                    $user = User::query()->create([
                        'firm_id' => $firm->id,
                        'role_id' => $customerRoleId,
                        'restaurant_id' => null,
                        'name' => $name !== '' ? $name : 'Müşteri',
                        'email' => $email,
                        'phone' => $phone !== '' ? $phone : null,
                        'password' => Str::random(32),
                        'status' => 'active',
                    ]);
                    $createdUsers++;
                }

                $restLat = $o->restaurant?->latitude !== null ? (float) $o->restaurant->latitude : 38.422;
                $restLng = $o->restaurant?->longitude !== null ? (float) $o->restaurant->longitude : 27.131;
                $lat = $restLat + ((random_int(-1000, 1000) / 1000) * 0.02);
                $lng = $restLng + ((random_int(-1000, 1000) / 1000) * 0.02);

                $addrText = $o->restaurant?->address ?: ($firm->district.', '.$firm->city);
                $addr = Address::query()->create([
                    'user_id' => $user->id,
                    'title' => 'Teslimat',
                    'address' => (string) $addrText,
                    'latitude' => $lat,
                    'longitude' => $lng,
                ]);
                $createdAddrs++;

                $o->update([
                    'user_id' => $o->user_id ?? $user->id,
                    'delivery_address_id' => $addr->id,
                ]);
                $updatedOrders++;
            }

            if ($dryRun) {
                DB::rollBack();
                $this->info("DRY RUN tamam. Oluşacak kullanıcı: {$createdUsers}, adres: {$createdAddrs}, güncellenecek sipariş: {$updatedOrders}");
                return self::SUCCESS;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Tamam. Yeni kullanıcı: {$createdUsers}, yeni adres: {$createdAddrs}, güncellenen sipariş: {$updatedOrders}");

        return self::SUCCESS;
    }
}

