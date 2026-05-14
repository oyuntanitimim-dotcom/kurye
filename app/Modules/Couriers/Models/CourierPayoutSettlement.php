<?php

declare(strict_types=1);

namespace App\Modules\Couriers\Models;

use App\Enums\CourierPayoutPaymentMethod;
use App\Enums\CourierPayoutSettlementStatus;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourierPayoutSettlement extends Model
{
    protected $table = 'courier_payout_settlements';

    protected $fillable = [
        'firm_id',
        'courier_id',
        'recorded_by_user_id',
        'period_start',
        'period_end',
        'period_preset',
        'earnings_from_orders',
        'orders_count',
        'ledger_deductions',
        'ledger_credits',
        'net_paid',
        'payment_method',
        'payment_reference',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'earnings_from_orders' => 'decimal:2',
            'ledger_deductions' => 'decimal:2',
            'ledger_credits' => 'decimal:2',
            'net_paid' => 'decimal:2',
        ];
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'courier_payout_settlement_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CourierLedgerEntry::class, 'settlement_id');
    }

    public function paymentMethodEnum(): ?CourierPayoutPaymentMethod
    {
        return CourierPayoutPaymentMethod::tryFrom((string) $this->payment_method);
    }

    public function statusEnum(): ?CourierPayoutSettlementStatus
    {
        return CourierPayoutSettlementStatus::tryFrom((string) $this->status);
    }
}
