<?php

declare(strict_types=1);

namespace App\Modules\Orders\Models;

use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Models\Firm;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDispatchDecision extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'firm_id',
        'chosen_courier_id',
        'candidates_json',
        'trigger',
        'created_by_user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'candidates_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function chosenCourier(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'chosen_courier_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
