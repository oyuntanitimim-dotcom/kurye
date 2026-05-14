<?php

namespace App\Modules\Firms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    protected $fillable = [
        'firm_id',
        'name',
        'discount_rate',
        'min_order',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'discount_rate' => 'decimal:2',
            'min_order' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }
}
