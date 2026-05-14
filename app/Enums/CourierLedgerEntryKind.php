<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Kurye cari: avans ve kesinti ciro hakkından düşer; kredi/bonus ciro hakkını artırır.
 */
enum CourierLedgerEntryKind: string
{
    case Advance = 'advance';
    case Expense = 'expense';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Advance => 'Avans / ön ödeme',
            self::Expense => 'Gider / kesinti',
            self::Credit => 'Kredi / prim',
        };
    }
}
