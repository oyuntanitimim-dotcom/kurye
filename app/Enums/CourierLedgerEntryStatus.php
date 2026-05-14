<?php

declare(strict_types=1);

namespace App\Enums;

enum CourierLedgerEntryStatus: string
{
    case Open = 'open';
    case Settled = 'settled';
    case Voided = 'voided';
}
