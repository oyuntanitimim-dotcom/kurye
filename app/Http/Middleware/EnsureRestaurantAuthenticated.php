<?php

namespace App\Http\Middleware;

use App\Modules\Users\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRestaurantAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || $user->role?->name !== Role::RESTAURANT) {
            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
