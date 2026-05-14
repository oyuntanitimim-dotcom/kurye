<?php

namespace App\Modules\Firms\Models;

use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Firm extends Model
{
    protected $fillable = [
        'name',
        'city',
        'district',
        'domain',
        'logo',
        'platform_fee_per_order',
        'default_restaurant_fee_per_delivery',
        'opening_hours',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'platform_fee_per_order' => 'decimal:2',
            'default_restaurant_fee_per_delivery' => 'decimal:2',
            'opening_hours' => 'array',
            'settings' => 'array',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mergedOperationSettings(): array
    {
        $defaults = [
            'auto_dispatch_enabled' => false,
            'auto_assign_best_after_eta' => false,
            'location_max_age_minutes' => (int) config('courier.dispatch_location_max_age_minutes', 15),
            'dispatch_weight_distance' => (float) config('courier.dispatch_weight_distance', 1.0),
            'dispatch_weight_active_orders' => (float) config('courier.dispatch_weight_active_orders', 2.0),
            'dispatch_weight_stale' => (float) config('courier.dispatch_weight_stale', 0.15),
            'default_delivery_fee' => (float) config('shop.default_delivery_fee', 15),
            'delivery_use_distance' => false,
            'delivery_distance_base_fee' => (float) config('shop.delivery_distance_base_fee', 15),
            'delivery_distance_per_km' => (float) config('shop.delivery_distance_per_km', 4),
            'delivery_distance_min_fee' => (float) config('shop.delivery_distance_min_fee', 10),
            'delivery_distance_max_fee' => (float) config('shop.delivery_distance_max_fee', 120),
        ];

        $s = is_array($this->settings) ? $this->settings : [];

        return array_merge($defaults, $s);
    }

    public function isAutoDispatchEnabled(): bool
    {
        return (bool) ($this->mergedOperationSettings()['auto_dispatch_enabled'] ?? false);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }
}
