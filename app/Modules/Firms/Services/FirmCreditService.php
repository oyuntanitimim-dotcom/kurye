<?php

declare(strict_types=1);

namespace App\Modules\Firms\Services;

use App\Models\PlatformSetting;
use App\Modules\Firms\Exceptions\InsufficientCreditException;
use App\Modules\Firms\Models\Firm;
use App\Modules\Firms\Models\FirmCreditTransaction;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\DB;

class FirmCreditService
{
    public function unitPrice(): float
    {
        return max(0.0, (float) PlatformSetting::get('credit_unit_price', 1.0));
    }

    public function globalCreditsPerOrder(): int
    {
        return max(1, (int) PlatformSetting::get('credits_per_order', 2));
    }

    public function creditsPerOrder(Firm $firm): int
    {
        $override = $firm->credits_per_order_override;
        if ($override !== null && (int) $override > 0) {
            return (int) $override;
        }

        return $this->globalCreditsPerOrder();
    }

    public function wasChargedForOrder(int $orderId): bool
    {
        return FirmCreditTransaction::query()
            ->where('order_id', $orderId)
            ->where('type', FirmCreditTransaction::TYPE_ORDER_DEDUCTION)
            ->exists();
    }

    /**
     * Bu sipariş için kurye ataması yapılabilir mi? (kontör yeterli veya zaten düşülmüş)
     */
    public function canAssign(Order $order): bool
    {
        if ($order->firm_id === null) {
            return false;
        }

        if ($this->wasChargedForOrder((int) $order->id)) {
            return true;
        }

        $firm = $order->relationLoaded('firm') && $order->firm !== null
            ? $order->firm
            : Firm::query()->find($order->firm_id);

        if ($firm === null) {
            return false;
        }

        return (int) $firm->credit_balance >= $this->creditsPerOrder($firm);
    }

    /**
     * Sipariş başına idempotent kontör düşümü (kurye ataması anında).
     *
     * @throws InsufficientCreditException
     */
    public function chargeForAssignment(Order $order): void
    {
        if ($order->firm_id === null) {
            return;
        }

        if ($this->wasChargedForOrder((int) $order->id)) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $firm = Firm::query()->whereKey($order->firm_id)->lockForUpdate()->first();
            if ($firm === null) {
                return;
            }

            // Kilit altında tekrar kontrol (yarış durumları)
            if ($this->wasChargedForOrder((int) $order->id)) {
                return;
            }

            $cost = $this->creditsPerOrder($firm);
            if ((int) $firm->credit_balance < $cost) {
                throw new InsufficientCreditException('Kontör yetersiz. Lütfen kontör yükleyin.');
            }

            $firm->credit_balance = (int) $firm->credit_balance - $cost;
            $firm->save();

            FirmCreditTransaction::query()->create([
                'firm_id' => $firm->id,
                'order_id' => $order->id,
                'type' => FirmCreditTransaction::TYPE_ORDER_DEDUCTION,
                'amount' => -$cost,
                'balance_after' => $firm->credit_balance,
                'description' => 'Sipariş #'.$order->id.' kurye ataması',
            ]);
        });
    }

    /**
     * Kontör ekler/çıkarır (satın alma, yönetici düzeltmesi, iade).
     */
    public function addCredits(
        Firm $firm,
        int $credits,
        string $type,
        ?string $description = null,
        ?int $byUserId = null,
        ?int $orderId = null
    ): FirmCreditTransaction {
        return DB::transaction(function () use ($firm, $credits, $type, $description, $byUserId, $orderId): FirmCreditTransaction {
            $locked = Firm::query()->whereKey($firm->id)->lockForUpdate()->firstOrFail();

            $newBalance = (int) $locked->credit_balance + $credits;
            if ($newBalance < 0) {
                $newBalance = 0;
            }
            $locked->credit_balance = $newBalance;
            $locked->save();

            $tx = FirmCreditTransaction::query()->create([
                'firm_id' => $locked->id,
                'order_id' => $orderId,
                'type' => $type,
                'amount' => $credits,
                'balance_after' => $newBalance,
                'description' => $description,
                'created_by' => $byUserId,
            ]);

            $firm->credit_balance = $newBalance;

            return $tx;
        });
    }
}
