<?php

declare(strict_types=1);

namespace App\Modules\Restaurants\Services;

use App\Enums\RestaurantBusinessType;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Restaurants\Models\RestaurantCategory;

class RestaurantMenuTemplateService
{
    public function apply(Restaurant $restaurant, RestaurantBusinessType $type): void
    {
        /** @var list<string> $names */
        $names = config('restaurant_menu_templates.types.'.$type->value, []);

        if ($names === []) {
            return;
        }

        foreach (array_values($names) as $order => $name) {
            RestaurantCategory::query()->create([
                'restaurant_id' => $restaurant->id,
                'name' => $name,
                'sort_order' => $order,
            ]);
        }
    }
}
