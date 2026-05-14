<?php

namespace App\Modules\Firms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    protected $fillable = [
        'firm_id',
        'code',
        'discount',
        'usage_limit',
        'used_count',
        'expire_date',
    ];

    protected function casts(): array
    {
        return [
            'discount' => 'decimal:2',
            'expire_date' => 'date',
        ];
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }
}
