<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Models;

use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationExternalOrder extends Model
{
    protected $fillable = [
        'firm_id',
        'provider',
        'external_order_id',
        'order_id',
        'last_payload_hash',
        'status',
        'error_message',
    ];

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
