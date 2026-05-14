<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Users\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UnifiedAuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->to(self::dashboardUrl(Auth::user()));
        }

        return view('auth.login', [
            'title' => 'Giriş',
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->to(self::dashboardUrl(Auth::user()));
        }

        $request->merge([
            'email' => trim((string) $request->input('email', '')),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Kullanıcı adı veya şifre hatalı.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        return redirect()->intended(self::dashboardUrl($user));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public static function dashboardUrl(User $user): string
    {
        return match ($user->role?->name) {
            Role::SUPER_ADMIN => route('admin.dashboard'),
            Role::FIRM_ADMIN => route('firm.dashboard'),
            Role::RESTAURANT => route('restaurant.dashboard'),
            Role::COURIER => route('courier.dashboard'),
            Role::CUSTOMER => route('shop.home'),
            default => route('shop.home'),
        };
    }
}
