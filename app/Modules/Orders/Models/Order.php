<?php

namespace App\Modules\Orders\Models;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Couriers\Models\CourierPayoutSettlement;
use App\Modules\Firms\Models\Firm;
use App\Modules\Integrations\Models\IntegrationExternalOrder;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Firms\Models\Campaign;
use App\Modules\Firms\Models\Coupon;
use App\Modules\Users\Models\Address;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'firm_id',
        'source',
        'user_id',
        'restaurant_id',
        'courier_id',
        'courier_payout_settlement_id',
        'restaurant_courier_requested_at',
        'delivery_address_id',
        'status',
        'total_price',
        'delivery_fee',
        'discount_amount',
        'payment_method',
        'campaign_id',
        'coupon_id',
        'notes',
        'customer_name',
        'customer_phone',
        'marketplace_provider',
        'tracking_token',
        'tracking_token_created_at',
        'tracking_revoked_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if ($order->tracking_token === null || $order->tracking_token === '') {
                $order->tracking_token = self::newUniqueTrackingToken();
                $order->tracking_token_created_at = now();
            }
        });
    }

    private static function newUniqueTrackingToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::query()->where('tracking_token', $token)->exists());

        return $token;
    }

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'platform_fee_amount' => 'decimal:2',
            'restaurant_commission_amount' => 'decimal:2',
            'courier_payout_amount' => 'decimal:2',
            'tracking_token_created_at' => 'datetime',
            'tracking_revoked_at' => 'datetime',
            'restaurant_courier_requested_at' => 'datetime',
        ];
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function courierPayoutSettlement(): BelongsTo
    {
        return $this->belongsTo(CourierPayoutSettlement::class, 'courier_payout_settlement_id');
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'delivery_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function integrationExternalOrder(): HasOne
    {
        return $this->hasOne(IntegrationExternalOrder::class);
    }

    public function dispatchDecisions(): HasMany
    {
        return $this->hasMany(OrderDispatchDecision::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function sourceEnum(): ?OrderSource
    {
        return OrderSource::tryFrom((string) $this->source);
    }

    /** Müşteri satırı: kayıtlı kullanıcı veya manuel/agregatör alanları */
    public function customerDisplayName(): string
    {
        if ($this->customer !== null) {
            return (string) $this->customer->name;
        }

        if ($this->customer_name !== null && $this->customer_name !== '') {
            return $this->customer_name;
        }

        return '—';
    }

    /** Ödeme yöntemi: müşteri online mı ödedi, kurye kapıda mı tahsil edecek (nakit/kart). */
    public function paymentMethodLabel(): string
    {
        return match ((string) $this->payment_method) {
            'online' => 'Online ödeme',
            'cash_on_delivery' => 'Kapıda nakit (kurye tahsilat)',
            'card_on_delivery' => 'Kapıda kart (kurye tahsilat)',
            'card' => 'Kart',
            default => $this->payment_method !== null && $this->payment_method !== ''
                ? (string) $this->payment_method
                : '—',
        };
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /** Restoran “Kurye çağır” dedikten sonra, firma tarafında atama kuyruğunda görünür. */
    public function scopeReadyForFirmCourierPool(Builder $query): Builder
    {
        return $query
            ->where('status', OrderStatus::Ready->value)
            ->whereNull('courier_id')
            ->whereNotNull('restaurant_courier_requested_at');
    }
}
