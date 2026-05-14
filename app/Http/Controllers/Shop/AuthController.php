<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use App\Services\FirmContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegister(FirmContext $firmContext): View
    {
        return view('shop.register', ['title' => 'Kayıt Ol', 'firm' => $firmContext->require()]);
    }

    public function register(Request $request, FirmContext $firmContext): RedirectResponse
    {
        $firm = $firmContext->require();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $roleId = Role::query()->where('name', Role::CUSTOMER)->value('id');

        $user = User::query()->create([
            'firm_id' => $firm->id,
            'role_id' => $roleId,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'status' => 'active',
        ]);

        Auth::login($user);

        return redirect()->route('shop.home');
    }

}
