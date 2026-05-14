<?php

declare(strict_types=1);

namespace App\Modules\Couriers\Models;

use App\Enums\CourierLedgerEntryKind;
use App\Enums\CourierLedgerEntryStatus;
use App\Modules\Firms\Models\Firm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierLedgerEntry extends Model
{
    protected $table = 'courier_ledger_entries';

    protected $fillable = [
        'firm_id',
        'courier_id',
        'entry_kind',
        'amount',
        'method',
        'reference',
        'description',
        'entry_date',
        'status',
        'settlement_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'entry_date' => 'date',
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

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(CourierPayoutSettlement::class, 'settlement_id');
    }

    public function kindEnum(): ?CourierLedgerEntryKind
    {
        return CourierLedgerEntryKind::tryFrom((string) $this->entry_kind);
    }

    public function statusEnum(): ?CourierLedgerEntryStatus
    {
        return CourierLedgerEntryStatus::tryFrom((string) $this->status);
    }
}
