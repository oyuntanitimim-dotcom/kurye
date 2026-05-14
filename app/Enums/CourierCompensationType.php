<?php

declare(strict_types=1);

namespace App\Enums;

enum CourierCompensationType: string
{
    case None = 'none';
    case PerDelivery = 'per_delivery';
    case MonthlySalary = 'monthly_salary';
    case PerKilometer = 'per_kilometer';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Tanımsız (elle takip)',
            self::PerDelivery => 'Teslim başı sabit ücret',
            self::MonthlySalary => 'Aylık maaş',
            self::PerKilometer => 'Km başı ücret',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
