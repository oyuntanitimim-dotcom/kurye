<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Models;

use App\Modules\Firms\Models\Firm;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationConnection extends Model
{
    protected $fillable = [
        'firm_id',
        'restaurant_id',
        'provider',
        'credentials_encrypted',
        'settings_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
