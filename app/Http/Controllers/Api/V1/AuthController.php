<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->merge([
            'email' => trim((string) $request->input('email', '')),
        ]);

        $data = $request->validate([
            'email' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string'],
            'role' => ['nullable', 'in:customer,courier,firm_admin'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kimlik bilgileri geçersiz.'],
            ]);
        }

        if (! empty($data['role'])) {
            $map = [
                'customer' => Role::CUSTOMER,
                'courier' => Role::COURIER,
                'firm_admin' => Role::FIRM_ADMIN,
            ];
            if ($user->role?->name !== $map[$data['role']]) {
                throw ValidationException::withMessages([
                    'role' => ['Bu uç nokta için rol uyuşmuyor.'],
                ]);
            }
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name,
                'firm_id' => $user->firm_id,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Çıkış yapıldı.']);
    }
}
