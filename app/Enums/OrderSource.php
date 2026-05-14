<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderSource: string
{
    case OwnShop = 'own_shop';

    case Phone = 'phone';

    case WalkIn = 'walk_in';

    /** Pazar yeri (Yemeksepeti, Trendyol Yemek vb.) — ayrıntı integration / provider alanında */
    case Marketplace = 'marketplace';

    public function label(): string
    {
        return match ($this) {
            self::OwnShop => 'Mağaza',
            self::Phone => 'Telefon',
            self::WalkIn => 'Dükkân / gel-al',
            self::Marketplace => 'Pazar yeri',
        };
    }
}
