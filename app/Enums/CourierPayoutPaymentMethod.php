<?php

declare(strict_types=1);

namespace App\Enums;

enum CourierPayoutPaymentMethod: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Eft = 'eft';
    case Check = 'check';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Nakit',
            self::BankTransfer => 'Havale',
            self::Eft => 'EFT',
            self::Check => 'Çek',
            self::Other => 'Diğer',
        };
    }
}
