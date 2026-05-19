<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pasif hesapların oturum ve API token erişimini keser.
 */
class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        if ((string) $user->status === 'active') {
            return $next($request);
        }

        $user->tokens()->delete();

        if ($request->is('api/*') || $request->expectsJson()) {
            abort(403, 'Hesap aktif değil.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors(['email' => 'Hesap aktif değil. Yönetici ile iletişime geçin.']);
    }
}
