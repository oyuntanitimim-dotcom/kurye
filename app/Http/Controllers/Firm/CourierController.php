<?php

namespace App\Http\Controllers\Firm;

use App\Enums\CourierCompensationType;
use App\Http\Controllers\Controller;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourierController extends Controller
{
    public function index(): View
    {
        $firmId = Auth::user()->firm_id;

        return view('firm.couriers.index', [
            'title' => 'Kuryeler',
            'couriers' => Courier::query()
                ->where('firm_id', $firmId)
                ->with('user')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('firm.couriers.create', ['title' => 'Yeni kurye']);
    }

    public function store(Request $request): RedirectResponse
    {
        $firmId = Auth::user()->firm_id;

        $request->merge([
            'email' => trim((string) $request->input('email', '')),
        ]);

        $data = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'vehicle_type' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'in:active,inactive'],
            // Giriş kimliği users.email sütununda tutulur; e-posta formatı zorunlu değildir.
            'email' => ['required', 'string', 'min:2', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ], $this->compensationRules($request)));

        $roleId = Role::query()->where('name', Role::COURIER)->value('id');

        // User + Courier atomik olusur; biri basarisiz olursa kullanici da geri alinir
        // (aksi halde e-postasi "alinmis" gorunen ama listede olmayan yetim kullanici kalir).
        DB::transaction(function () use ($firmId, $roleId, $data): void {
            $user = User::query()->create([
                'firm_id' => $firmId,
                'restaurant_id' => null,
                'role_id' => $roleId,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'status' => 'active',
            ]);

            Courier::query()->create(array_merge([
                'firm_id' => $firmId,
                'user_id' => $user->id,
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'vehicle_type' => $data['vehicle_type'] ?? null,
                'status' => $data['status'],
            ], $this->normalizedCompensation($data)));
        });

        return redirect()->route('firm.couriers.index')->with('status', 'Kurye oluşturuldu.');
    }

    public function edit(Courier $courier): View
    {
        $this->assertFirmCourier($courier);
        $courier->load('user');

        return view('firm.couriers.edit', [
            'title' => 'Kurye düzenle',
            'courier' => $courier,
        ]);
    }

    public function update(Request $request, Courier $courier): RedirectResponse
    {
        $this->assertFirmCourier($courier);
        $courier->load('user');

        $request->merge([
            'email' => trim((string) $request->input('email', '')),
        ]);

        $data = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'vehicle_type' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'in:active,inactive'],
            'email' => [
                'required', 'string', 'min:2', 'max:190',
                Rule::unique('users', 'email')->ignore($courier->user_id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
        ], $this->compensationRules($request)));

        if ($data['status'] === 'inactive' && $this->courierHasActiveOrders($courier)) {
            return back()->withErrors(['status' => 'Aktif siparişi varken kurye pasif yapılamaz.'])->withInput();
        }

        $courier->update(array_merge([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'status' => $data['status'],
        ], $this->normalizedCompensation($data)));

        $userPayload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'] === 'active' ? 'active' : 'inactive',
        ];

        if (! empty($data['password'])) {
            $userPayload['password'] = Hash::make($data['password']);
        }

        $courier->user->update($userPayload);

        return redirect()->route('firm.couriers.index')->with('status', 'Kurye güncellendi.');
    }

    private function assertFirmCourier(Courier $courier): void
    {
        if ((int) $courier->firm_id !== (int) Auth::user()->firm_id) {
            abort(403);
        }
    }

    private function courierHasActiveOrders(Courier $courier): bool
    {
        return Order::query()
            ->where('courier_id', $courier->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->exists();
    }

    /**
     * @return array<string, array<int, mixed|\Closure|string>>
     */
    private function compensationRules(Request $request): array
    {
        return [
            'compensation_type' => ['required', Rule::in(CourierCompensationType::values())],
            'compensation_per_delivery' => [
                'nullable', 'numeric', 'min:0', 'max:999999.99',
                Rule::requiredIf(fn () => $request->input('compensation_type') === CourierCompensationType::PerDelivery->value),
            ],
            'compensation_monthly_salary' => [
                'nullable', 'numeric', 'min:0', 'max:999999.99',
                Rule::requiredIf(fn () => $request->input('compensation_type') === CourierCompensationType::MonthlySalary->value),
            ],
            'compensation_per_km' => [
                'nullable', 'numeric', 'min:0', 'max:9999.9999',
                Rule::requiredIf(fn () => $request->input('compensation_type') === CourierCompensationType::PerKilometer->value),
            ],
            'compensation_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizedCompensation(array $data): array
    {
        $type = (string) $data['compensation_type'];

        return [
            'compensation_type' => $type,
            'compensation_per_delivery' => $type === CourierCompensationType::PerDelivery->value
                ? ($data['compensation_per_delivery'] ?? null)
                : null,
            'compensation_monthly_salary' => $type === CourierCompensationType::MonthlySalary->value
                ? ($data['compensation_monthly_salary'] ?? null)
                : null,
            'compensation_per_km' => $type === CourierCompensationType::PerKilometer->value
                ? ($data['compensation_per_km'] ?? null)
                : null,
            'compensation_notes' => $data['compensation_notes'] ?? null,
        ];
    }
}
