<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Mobil uygulama: yalnızca bu roller API token alabilir. */
    private const MOBILE_API_ROLES = [
        Role::COURIER,
        Role::FIRM_ADMIN,
        Role::RESTAURANT,
    ];

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

        if ((string) $user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Hesap aktif değil. Yönetici ile iletişime geçin.'],
            ]);
        }

        $roleName = $user->role?->name;
        if ($roleName === null || ! in_array($roleName, self::MOBILE_API_ROLES, true)) {
            throw ValidationException::withMessages([
                'email' => ['Bu uygulama için yetkili hesap bulunamadı.'],
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

        // Çalınmış eski tokenları geçersiz kıl (tek aktif oturum)
        $user->tokens()->delete();

        $expiresAt = Carbon::now()->addMinutes(
            max(60, (int) config('sanctum.expiration', 60 * 24 * 30))
        );
        $abilities = match ($roleName) {
            Role::COURIER => ['role:courier'],
            Role::FIRM_ADMIN => ['role:firm_admin'],
            Role::RESTAURANT => ['role:restaurant'],
            default => [],
        };
        $token = $user->createToken('mobile-api', $abilities, $expiresAt)->plainTextToken;

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
