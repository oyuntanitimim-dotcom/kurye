<?php

namespace App\Modules\Restaurants\Models;

use App\Modules\Orders\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Restaurants\Models\ProductImage;

class Product extends Model
{
    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'description',
        'price',
        'discounted_price',
        'image',
        'status',
        'stock',
        'prep_time_minutes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discounted_price' => 'decimal:2',
        ];
    }

    /** Satışta kullanılan birim fiyat (indirimli tanımlı ve liste fiyatından düşükse o). */
    public function effectiveUnitPrice(): float
    {
        $list = (float) $this->price;
        if ($this->discounted_price === null) {
            return $list;
        }
        $d = (float) $this->discounted_price;

        return min($list, $d);
    }

    public function imageUrl(): ?string
    {
        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            $primary = $this->images->firstWhere('is_primary', true) ?? $this->images->sortBy('sort_order')->first();
            $url = $primary?->url();
            if ($url !== null) {
                return $url;
            }
        }

        if ($this->image === null || $this->image === '') {
            return null;
        }
        if (str_starts_with((string) $this->image, 'http://') || str_starts_with((string) $this->image, 'https://')) {
            return (string) $this->image;
        }

        return asset('storage/'.$this->image);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RestaurantCategory::class, 'category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
