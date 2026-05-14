<?php

declare(strict_types=1);

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingMenu extends Model
{
    protected $fillable = [
        'marketing_site_id',
        'key',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(MarketingSite::class, 'marketing_site_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MarketingMenuItem::class, 'marketing_menu_id')->orderBy('sort_order');
    }

    public function rootItems(): HasMany
    {
        return $this->items()->whereNull('parent_id');
    }
}
