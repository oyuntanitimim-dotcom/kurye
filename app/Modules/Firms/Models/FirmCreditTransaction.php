<?php

declare(strict_types=1);

namespace App\Modules\Firms\Models;

use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirmCreditTransaction extends Model
{
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_ORDER_DEDUCTION = 'order_deduction';
    public const TYPE_ADMIN_ADJUSTMENT = 'admin_adjustment';
    public const TYPE_REFUND = 'refund';

    protected $fillable = [
        'firm_id',
        'order_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_PURCHASE => 'Satın alma',
            self::TYPE_ORDER_DEDUCTION => 'Sipariş düşümü',
            self::TYPE_ADMIN_ADJUSTMENT => 'Yönetici düzeltmesi',
            self::TYPE_REFUND => 'İade',
            default => $this->type,
        };
    }
}
