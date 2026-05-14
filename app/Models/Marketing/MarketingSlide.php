<?php

declare(strict_types=1);

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingSlide extends Model
{
    protected $fillable = [
        'marketing_site_id',
        'title',
        'subtitle',
        'image_path',
        'cta_label',
        'cta_url',
        'sort_order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(MarketingSite::class, 'marketing_site_id');
    }
}
