<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Firm;

use App\Enums\CourierCompensationType;
use App\Http\Controllers\Controller;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CouriersController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    public function index(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }

        $firmId = (int) $u->firm_id;

        $items = Courier::query()
            ->where('firm_id', $firmId)
            ->with('location')
            ->orderBy('status')
            ->orderBy('name')
            ->paginate(30);

        $items->getCollection()->transform(function (Courier $c): array {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'vehicle_type' => $c->vehicle_type,
                'status' => $c->status,
                'email' => $c->user?->email,
                'lat' => $c->location?->latitude !== null ? (float) $c->location->latitude : null,
                'lng' => $c->location?->longitude !== null ? (float) $c->location->longitude : null,
                'location_updated_at' => $c->location?->updated_at?->toIso8601String(),
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
            'name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'vehicle_type' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'email' => ['required', 'string', 'min:2', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $roleId = Role::query()->where('name', Role::COURIER)->value('id');

        $user = User::query()->create([
            'firm_id' => $firmId,
            'restaurant_id' => null,
            'role_id' => $roleId,
            'name' => $payload['name'],
            'email' => trim((string) $payload['email']),
            'phone' => $payload['phone'] ?? null,
            'password' => Hash::make((string) $payload['password']),
            'status' => (($payload['status'] ?? 'active') === 'active') ? 'active' : 'inactive',
        ]);

        $courier = Courier::query()->create([
            'firm_id' => $firmId,
            'user_id' => $user->id,
            'name' => $payload['name'],
            'phone' => $payload['phone'] ?? null,
            'vehicle_type' => $payload['vehicle_type'] ?? null,
            'status' => $payload['status'] ?? 'active',
            'compensation_type' => CourierCompensationType::None->value,
        ]);

        return response()->json([
            'ok' => true,
            'courier_id' => $courier->id,
        ], 201);
    }

    public function update(Request $request, Courier $courier): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }
        if ((int) $courier->firm_id !== (int) $u->firm_id) {
            abort(403);
        }

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'vehicle_type' => ['nullable', 'string', 'max:64'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'email' => ['required', 'string', 'min:2', 'max:190', Rule::unique('users', 'email')->ignore($courier->user_id)],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if ($payload['status'] === 'inactive' && $this->hasOpenOrders($courier)) {
            return response()->json(['message' => 'Aktif siparişi varken kurye pasif yapılamaz.'], 422);
        }

        $courier->update([
            'name' => $payload['name'],
            'phone' => $payload['phone'] ?? null,
            'vehicle_type' => $payload['vehicle_type'] ?? null,
            'status' => $payload['status'],
        ]);

        $userPayload = [
            'name' => $payload['name'],
            'email' => trim((string) $payload['email']),
            'phone' => $payload['phone'] ?? null,
            'status' => $payload['status'] === 'active' ? 'active' : 'inactive',
        ];
        if (! empty($payload['password'])) {
            $userPayload['password'] = Hash::make((string) $payload['password']);
        }
        $courier->user()->update($userPayload);

        return response()->json(['ok' => true]);
    }

    public function setStatus(Request $request, Courier $courier): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }
        if ((int) $courier->firm_id !== (int) $u->firm_id) {
            abort(403);
        }

        $payload = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $status = (string) $payload['status'];
        if ($status === 'inactive' && $this->hasOpenOrders($courier)) {
            return response()->json(['message' => 'Aktif siparişi varken kurye pasif yapılamaz.'], 422);
        }

        $courier->update(['status' => $status]);
        $courier->user()->update(['status' => $status === 'active' ? 'active' : 'inactive']);

        return response()->json(['ok' => true]);
    }

    private function hasOpenOrders(Courier $courier): bool
    {
        return Order::query()
            ->where('courier_id', $courier->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->exists();
    }
}

