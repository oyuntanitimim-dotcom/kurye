<?php

declare(strict_types=1);

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingSite extends Model
{
    /** @use HasFactory<\Database\Factories\Marketing\MarketingSiteFactory> */
    use HasFactory;

    protected static function newFactory(): \Database\Factories\Marketing\MarketingSiteFactory
    {
        return \Database\Factories\Marketing\MarketingSiteFactory::new();
    }

    protected $fillable = [
        'name',
        'primary_domain',
        'is_published',
        'theme',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'theme' => 'array',
        ];
    }

    public function pages(): HasMany
    {
        return $this->hasMany(MarketingPage::class, 'marketing_site_id');
    }

    public function menus(): HasMany
    {
        return $this->hasMany(MarketingMenu::class, 'marketing_site_id');
    }

    public function slides(): HasMany
    {
        return $this->hasMany(MarketingSlide::class, 'marketing_site_id');
    }

    public function footerColumns(): HasMany
    {
        return $this->hasMany(MarketingFooterColumn::class, 'marketing_site_id');
    }
}
