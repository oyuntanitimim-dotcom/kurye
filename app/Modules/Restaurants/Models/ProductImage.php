<?php

namespace App\Modules\Restaurants\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'path',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function url(): ?string
    {
        if ($this->path === null || $this->path === '') {
            return null;
        }
        if (str_starts_with((string) $this->path, 'http://') || str_starts_with((string) $this->path, 'https://')) {
            return (string) $this->path;
        }

        return asset('storage/'.$this->path);
    }
}

