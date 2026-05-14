<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CourierLedgerEntryKind;
use App\Enums\CourierLedgerEntryStatus;
use App\Enums\CourierPayoutPaymentMethod;
use App\Enums\CourierPayoutSettlementStatus;
use App\Enums\OrderStatus;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Couriers\Models\CourierLedgerEntry;
use App\Modules\Couriers\Models\CourierPayoutSettlement;
use App\Modules\Orders\Models\Order;
use App\Support\FinanceReporting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class CourierPayoutService
{
    /**
     * @return array{orders: Collection<int, Order>, orders_total: float, ledgers: Collection<int, CourierLedgerEntry>, ledger_deductions: float, ledger_credits: float, net: float}
     */
    public function preview(
        int $firmId,
        int $courierId,
        Carbon $periodStart,
        Carbon $periodEnd,
        bool $includeAllOpenLedgers,
        bool $ordersAllTime = false
    ): array {
        if (! $this->assertCourierFirm($firmId, $courierId)) {
            return [
                'orders' => new Collection,
                'orders_total' => 0.0,
                'ledgers' => new Collection,
                'ledger_deductions' => 0.0,
                'ledger_credits' => 0.0,
                'net' => 0.0,
            ];
        }

        $orders = $this->unpaidOrdersQuery($firmId, $courierId, $periodStart, $periodEnd, $ordersAllTime)->get();
        $ordersTotal = (float) $orders->sum(function (Order $o): float {
            return (float) ($o->courier_payout_amount ?? 0);
        });

        $ledgerQ = CourierLedgerEntry::query()
            ->where('firm_id', $firmId)
            ->where('courier_id', $courierId)
            ->where('status', CourierLedgerEntryStatus::Open->value);

        if ($includeAllOpenLedgers) {
            $ledgers = $ledgerQ->orderBy('entry_date')->orderBy('id')->get();
        } else {
            $ledgers = $ledgerQ
                ->whereDate('entry_date', '>=', $periodStart->toDateString())
                ->whereDate('entry_date', '<=', $periodEnd->toDateString())
                ->orderBy('entry_date')
                ->orderBy('id')
                ->get();
        }

        $d = 0.0;
        $c = 0.0;
        foreach ($ledgers as $row) {
            $kind = CourierLedgerEntryKind::tryFrom((string) $row->entry_kind);
            $a = (float) $row->amount;
            if ($kind === null) {
                continue;
            }
            if (in_array($kind, [CourierLedgerEntryKind::Advance, CourierLedgerEntryKind::Expense], true)) {
                $d += $a;
            } elseif ($kind === CourierLedgerEntryKind::Credit) {
                $c += $a;
            }
        }

        $net = $ordersTotal - $d + $c;

        return [
            'orders' => $orders,
            'orders_total' => $ordersTotal,
            'ledgers' => $ledgers,
            'ledger_deductions' => $d,
            'ledger_credits' => $c,
            'net' => $net,
        ];
    }

    public function createSettlement(
        int $firmId,
        int $courierId,
        int $userId,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?string $periodPreset,
        CourierPayoutPaymentMethod $paymentMethod,
        ?string $paymentReference,
        ?string $notes,
        bool $includeAllOpenLedgers,
        bool $ordersAllTime = false
    ): CourierPayoutSettlement {
        $data = $this->preview(
            $firmId,
            $courierId,
            $periodStart,
            $periodEnd,
            $includeAllOpenLedgers,
            $ordersAllTime
        );

        if ($data['orders']->isEmpty() && $data['ledgers']->isEmpty()) {
            throw new \InvalidArgumentException('Bu dönemde ödenecek sipariş hakkı veya kapatılacak cari kalem yok.');
        }

        return DB::transaction(function () use ($data, $firmId, $courierId, $userId, $periodStart, $periodEnd, $periodPreset, $paymentMethod, $paymentReference, $notes) {
            $settlement = CourierPayoutSettlement::query()->create([
                'firm_id' => $firmId,
                'courier_id' => $courierId,
                'recorded_by_user_id' => $userId,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'period_preset' => $periodPreset,
                'earnings_from_orders' => $data['orders_total'],
                'orders_count' => $data['orders']->count(),
                'ledger_deductions' => $data['ledger_deductions'],
                'ledger_credits' => $data['ledger_credits'],
                'net_paid' => $data['net'],
                'payment_method' => $paymentMethod->value,
                'payment_reference' => $paymentReference,
                'status' => CourierPayoutSettlementStatus::Paid->value,
                'notes' => $notes,
            ]);

            foreach ($data['orders'] as $order) {
                Order::query()->whereKey($order->id)->update([
                    'courier_payout_settlement_id' => $settlement->id,
                ]);
            }

            foreach ($data['ledgers'] as $ledger) {
                CourierLedgerEntry::query()->whereKey($ledger->id)->update([
                    'settlement_id' => $settlement->id,
                    'status' => CourierLedgerEntryStatus::Settled->value,
                ]);
            }

            return $settlement->fresh();
        });
    }

    public function voidSettlement(CourierPayoutSettlement $settlement): void
    {
        if ($settlement->status === CourierPayoutSettlementStatus::Voided->value) {
            return;
        }

        DB::transaction(function () use ($settlement): void {
            Order::query()
                ->where('courier_payout_settlement_id', $settlement->id)
                ->update(['courier_payout_settlement_id' => null]);

            CourierLedgerEntry::query()
                ->where('settlement_id', $settlement->id)
                ->update([
                    'settlement_id' => null,
                    'status' => CourierLedgerEntryStatus::Open->value,
                ]);

            $settlement->update([
                'status' => CourierPayoutSettlementStatus::Voided->value,
            ]);
        });
    }

    private function assertCourierFirm(int $firmId, int $courierId): bool
    {
        return Courier::query()
            ->where('firm_id', $firmId)
            ->whereKey($courierId)
            ->exists();
    }

    private function unpaidOrdersQuery(
        int $firmId,
        int $courierId,
        Carbon $from,
        Carbon $to,
        bool $allTime = false
    ): \Illuminate\Database\Eloquent\Builder {
        $q = Order::query()
            ->where('firm_id', $firmId)
            ->where('courier_id', $courierId)
            ->where('status', OrderStatus::Delivered->value)
            ->whereNull('courier_payout_settlement_id');
        if (! $allTime) {
            $q->whereBetween('updated_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ]);
        }
        FinanceReporting::restrictToOnlinePayment($q);

        return $q->orderBy('updated_at');
    }
}
