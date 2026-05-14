<?php

namespace App\Modules\Restaurants\Models;

use App\Enums\RestaurantBusinessType;
use App\Modules\Firms\Models\Firm;
use App\Modules\Integrations\Models\IntegrationConnection;
use App\Modules\Integrations\Models\IntegrationProductMap;
use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    protected $fillable = [
        'firm_id',
        'name',
        'logo',
        'phone',
        'address',
        'latitude',
        'longitude',
        'status',
        'business_type',
        'fee_per_delivery',
        'shop_delivery_fee',
        'opening_time',
        'closing_time',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'fee_per_delivery' => 'decimal:2',
            'shop_delivery_fee' => 'decimal:2',
            'business_type' => RestaurantBusinessType::class,
        ];
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(RestaurantCategory::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(User::class, 'restaurant_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function integrationConnections(): HasMany
    {
        return $this->hasMany(IntegrationConnection::class);
    }

    public function integrationProductMaps(): HasMany
    {
        return $this->hasMany(IntegrationProductMap::class);
    }
}
