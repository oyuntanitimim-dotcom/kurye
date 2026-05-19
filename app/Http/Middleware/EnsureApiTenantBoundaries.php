<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Couriers\Models\Courier;
use App\Modules\Couriers\Models\CourierPayoutSettlement;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route model üzerinden başka firmaya ait kayıtlara erişimi engeller (defense in depth).
 */
class EnsureApiTenantBoundaries
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        $order = $request->route('order');
        if ($order instanceof Order) {
            $this->assertOrder($user, $order);
        }

        $courier = $request->route('courier');
        if ($courier instanceof Courier) {
            $this->assertSameFirm($user, (int) $courier->firm_id);
        }

        $restaurant = $request->route('restaurant');
        if ($restaurant instanceof Restaurant) {
            $this->assertSameFirm($user, (int) $restaurant->firm_id);
        }

        $settlement = $request->route('settlement');
        if ($settlement instanceof CourierPayoutSettlement) {
            $this->assertSameFirm($user, (int) $settlement->firm_id);
        }

        return $next($request);
    }

    private function assertOrder(mixed $user, Order $order): void
    {
        $role = $user->role?->name;

        if ($role === Role::FIRM_ADMIN) {
            $this->assertSameFirm($user, (int) $order->firm_id);

            return;
        }

        if ($role === Role::RESTAURANT) {
            if ((int) $order->restaurant_id !== (int) $user->restaurant_id) {
                abort(403, 'Bu siparişe erişim yok.');
            }

            return;
        }

        if ($role === Role::COURIER) {
            $courierId = $user->courierProfile?->id;
            if ($courierId === null || (int) $order->courier_id !== (int) $courierId) {
                abort(403, 'Bu siparişe erişim yok.');
            }

            return;
        }

        if ($role === Role::CUSTOMER) {
            if ((int) $order->user_id !== (int) $user->id) {
                abort(403, 'Bu siparişe erişim yok.');
            }
            $this->assertSameFirm($user, (int) $order->firm_id);

            return;
        }

        abort(403, 'Bu siparişe erişim yok.');
    }

    private function assertSameFirm(mixed $user, int $resourceFirmId): void
    {
        $userFirmId = (int) ($user->firm_id ?? 0);
        if ($userFirmId < 1 || $resourceFirmId !== $userFirmId) {
            abort(403, 'Bu kayda erişim yok.');
        }
    }
}
