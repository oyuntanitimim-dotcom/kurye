<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Firm;

use App\Enums\RestaurantBusinessType;
use App\Http\Controllers\Controller;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RestaurantsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }

        $items = Restaurant::query()
            ->where('firm_id', (int) $u->firm_id)
            ->withCount('products')
            ->latest()
            ->paginate(30);

        $items->getCollection()->transform(function (Restaurant $r): array {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'phone' => $r->phone,
                'address' => $r->address,
                'status' => $r->status,
                'business_type' => $r->business_type?->value ?? 'restaurant',
                'products_count' => (int) ($r->products_count ?? 0),
            ];
        });

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }
        $firmId = (int) $u->firm_id;

        $payload = $request->validate([
            'business_type' => ['required', Rule::enum(RestaurantBusinessType::class)],
            'name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'admin_name' => ['required', 'string', 'max:190'],
            'admin_email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        $businessType = $payload['business_type'] instanceof RestaurantBusinessType
            ? $payload['business_type']
            : RestaurantBusinessType::from((string) $payload['business_type']);

        $restaurant = Restaurant::query()->create([
            'firm_id' => $firmId,
            'name' => $payload['name'],
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'status' => $payload['status'],
            'business_type' => $businessType,
        ]);

        $roleId = Role::query()->where('name', Role::RESTAURANT)->value('id');
        User::query()->create([
            'firm_id' => $firmId,
            'restaurant_id' => $restaurant->id,
            'role_id' => $roleId,
            'name' => $payload['admin_name'],
            'email' => $payload['admin_email'],
            'password' => Hash::make((string) $payload['admin_password']),
            'status' => $payload['status'] === 'active' ? 'active' : 'inactive',
        ]);

        return response()->json(['ok' => true, 'restaurant_id' => $restaurant->id], 201);
    }

    public function update(Request $request, Restaurant $restaurant): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }
        if ((int) $restaurant->firm_id !== (int) $u->firm_id) {
            abort(403);
        }

        $payload = $request->validate([
            'business_type' => ['required', Rule::enum(RestaurantBusinessType::class)],
            'name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $businessType = $payload['business_type'] instanceof RestaurantBusinessType
            ? $payload['business_type']
            : RestaurantBusinessType::from((string) $payload['business_type']);

        $restaurant->update([
            'name' => $payload['name'],
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'status' => $payload['status'],
            'business_type' => $businessType,
        ]);

        $restaurant->staff()
            ->whereHas('role', fn ($q) => $q->where('name', Role::RESTAURANT))
            ->update(['status' => $payload['status'] === 'active' ? 'active' : 'inactive']);

        return response()->json(['ok' => true]);
    }

    public function setStatus(Request $request, Restaurant $restaurant): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }
        if ((int) $restaurant->firm_id !== (int) $u->firm_id) {
            abort(403);
        }

        $payload = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
        $status = (string) $payload['status'];

        $restaurant->update(['status' => $status]);
        $restaurant->staff()
            ->whereHas('role', fn ($q) => $q->where('name', Role::RESTAURANT))
            ->update(['status' => $status === 'active' ? 'active' : 'inactive']);

        return response()->json(['ok' => true]);
    }
}

