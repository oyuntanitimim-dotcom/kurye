<?php

declare(strict_types=1);

namespace App\Enums;

enum RestaurantBusinessType: string
{
    case Restaurant = 'restaurant';
    case FastFood = 'fast_food';
    case Kebab = 'kebab';
    case Cafe = 'cafe';
    case Bakery = 'bakery';
    case Market = 'market';
    case Grocery = 'grocery';
    case Greengrocer = 'greengrocer';
    case Pharmacy = 'pharmacy';
    case Florist = 'florist';
    case Pet = 'pet';
    case NutsConfectionery = 'nuts_confectionery';
    case Gift = 'gift';
    case BookstoreStationery = 'bookstore_stationery';
    case WaterBeverageDispatch = 'water_beverage_dispatch';

    public function label(): string
    {
        return match ($this) {
            self::Restaurant => 'Restoran & esnaf lokantası',
            self::FastFood => 'Fast food & tost / burger',
            self::Kebab => 'Kebap & döner & pide',
            self::Cafe => 'Kahve & çay & içecek dükkanı',
            self::Bakery => 'Fırın & pastane',
            self::Market => 'Market & süpermarket',
            self::Grocery => 'Bakkal & şarküteri',
            self::Greengrocer => 'Manav & organik',
            self::Pharmacy => 'Eczane & sağlık ürünleri',
            self::Florist => 'Çiçekçi & süs bitkisi',
            self::Pet => 'Pet shop & mama / aksesuar',
            self::NutsConfectionery => 'Kuruyemiş & şekerleme',
            self::Gift => 'Hediyelik & şımartma paketleri',
            self::BookstoreStationery => 'Kitap & kırtasiye & ofis',
            self::WaterBeverageDispatch => 'Su damacana & içecek dağıtım',
        };
    }
}
