<?php

namespace App\Modules\Couriers\Models;

use App\Enums\CourierCompensationType;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Courier extends Model
{
    protected $fillable = [
        'firm_id',
        'user_id',
        'name',
        'phone',
        'vehicle_type',
        'status',
        'compensation_type',
        'compensation_per_delivery',
        'compensation_monthly_salary',
        'compensation_per_km',
        'compensation_notes',
    ];

    protected function casts(): array
    {
        return [
            'compensation_per_delivery' => 'decimal:2',
            'compensation_monthly_salary' => 'decimal:2',
            'compensation_per_km' => 'decimal:4',
        ];
    }

    public function compensationTypeEnum(): CourierCompensationType
    {
        return CourierCompensationType::tryFrom((string) $this->compensation_type)
            ?? CourierCompensationType::None;
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): HasOne
    {
        return $this->hasOne(CourierLocation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CourierLedgerEntry::class);
    }

    public function payoutSettlements(): HasMany
    {
        return $this->hasMany(CourierPayoutSettlement::class);
    }
}
