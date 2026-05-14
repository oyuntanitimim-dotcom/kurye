<?php

declare(strict_types=1);

namespace App\Support;

use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;

final class RestaurantPrimaryManager
{
    /**
     * Restoran paneli (/restoran) için bu işletmeye atanmış ilk “Restoran” rolü kullanıcısı.
     */
    public static function user(Restaurant $restaurant): ?User
    {
        $roleId = Role::query()->where('name', Role::RESTAURANT)->value('id');
        if ($roleId === null) {
            return null;
        }

        return User::query()
            ->where('firm_id', $restaurant->firm_id)
            ->where('restaurant_id', $restaurant->id)
            ->where('role_id', $roleId)
            ->orderBy('id')
            ->first();
    }
}
