<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Models;

use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationProductMap extends Model
{
    protected $fillable = [
        'restaurant_id',
        'provider',
        'external_sku',
        'product_id',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
