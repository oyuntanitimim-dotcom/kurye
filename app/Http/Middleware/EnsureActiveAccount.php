<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Modules\Users\Models\Role;
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

        $isActiveUser = (string) $user->status === 'active';
        $role = $user->role?->name;

        // Süper admin için firma durumu kontrol edilmez (çok tenant'lı yönetim ekranlarında gerekli).
        $isFirmActive = true;
        if ($role !== Role::SUPER_ADMIN && (int) ($user->firm_id ?? 0) > 0) {
            $firmStatus = (string) ($user->firm?->status ?? '');
            $isFirmActive = $firmStatus === '' || $firmStatus === 'active';
        }

        if ($isActiveUser && $isFirmActive) {
            return $next($request);
        }

        $user->tokens()->delete();

        if ($request->is('api/*') || $request->expectsJson()) {
            abort(403, $isActiveUser ? 'Firma aktif değil.' : 'Hesap aktif değil.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors(['email' => $isActiveUser ? 'Firma aktif değil. Yönetici ile iletişime geçin.' : 'Hesap aktif değil. Yönetici ile iletişime geçin.']);
    }
}
