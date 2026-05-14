<?php

declare(strict_types=1);

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingFooterColumn extends Model
{
    protected $fillable = [
        'marketing_site_id',
        'heading',
        'sort_order',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(MarketingSite::class, 'marketing_site_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(MarketingFooterLink::class, 'marketing_footer_column_id')->orderBy('sort_order');
    }
}
