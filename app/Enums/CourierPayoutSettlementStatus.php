<?php

declare(strict_types=1);

namespace App\Enums;

enum CourierPayoutSettlementStatus: string
{
    case Paid = 'paid';
    case Voided = 'voided';
}
