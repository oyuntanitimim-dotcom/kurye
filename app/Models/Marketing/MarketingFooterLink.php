<?php

declare(strict_types=1);

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingFooterLink extends Model
{
    protected $fillable = [
        'marketing_footer_column_id',
        'label',
        'url',
        'open_in_new_tab',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
        ];
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(MarketingFooterColumn::class, 'marketing_footer_column_id');
    }
}
