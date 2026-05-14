<?php

declare(strict_types=1);

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingPage extends Model
{
    protected $fillable = [
        'marketing_site_id',
        'slug',
        'title',
        'meta_description',
        'status',
        'published_at',
        'published_version_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(MarketingSite::class, 'marketing_site_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MarketingPageVersion::class, 'marketing_page_id')->orderByDesc('version');
    }

    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(MarketingPageVersion::class, 'published_version_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_version_id !== null;
    }
}
