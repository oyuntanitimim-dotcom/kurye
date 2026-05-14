<?php

namespace App\Http\Controllers\Firm;

use App\Enums\RestaurantBusinessType;
use App\Http\Controllers\Controller;
use App\Modules\Restaurants\Actions\SyncRestaurantPanelSettings;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Restaurants\Services\RestaurantMenuTemplateService;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\RestaurantPrimaryManager;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function index(): View
    {
        $firmId = Auth::user()->firm_id;

        return view('firm.restaurants.index', [
            'title' => 'Restoranlar',
            'restaurants' => Restaurant::query()
                ->where('firm_id', $firmId)
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('firm.restaurants.create', ['title' => 'Yeni restoran']);
    }

    public function store(Request $request): RedirectResponse
    {
        $firmId = Auth::user()->firm_id;

        $request->merge([
            'opening_time' => $request->filled('opening_time') ? $request->input('opening_time') : null,
            'closing_time' => $request->filled('closing_time') ? $request->input('closing_time') : null,
            'shop_delivery_fee' => $request->filled('shop_delivery_fee') ? $request->input('shop_delivery_fee') : null,
        ]);

        $data = $request->validate([
            'business_type' => ['required', Rule::enum(RestaurantBusinessType::class)],
            'name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'opening_time' => ['nullable', 'date_format:H:i'],
            'closing_time' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'in:active,inactive'],
            'fee_per_delivery' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'shop_delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'admin_name' => ['required', 'string', 'max:190'],
            'admin_email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        $businessType = $data['business_type'] instanceof RestaurantBusinessType
            ? $data['business_type']
            : RestaurantBusinessType::from((string) $data['business_type']);

        $restaurant = Restaurant::query()->create([
            'firm_id' => $firmId,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'opening_time' => $data['opening_time'] ?? null,
            'closing_time' => $data['closing_time'] ?? null,
            'status' => $data['status'],
            'business_type' => $businessType,
            'fee_per_delivery' => $data['fee_per_delivery'] ?? null,
            'shop_delivery_fee' => isset($data['shop_delivery_fee']) && $data['shop_delivery_fee'] !== null
                ? round((float) $data['shop_delivery_fee'], 2)
                : null,
        ]);

        app(RestaurantMenuTemplateService::class)->apply($restaurant, $businessType);

        $roleId = Role::query()->where('name', Role::RESTAURANT)->value('id');

        User::query()->create([
            'firm_id' => $firmId,
            'restaurant_id' => $restaurant->id,
            'role_id' => $roleId,
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => Hash::make($data['admin_password']),
            'status' => 'active',
        ]);

        return redirect()->route('firm.restaurants.index')->with('status', 'Restoran ve yönetici hesabı oluşturuldu.');
    }

    public function edit(Restaurant $restaurant): View
    {
        $this->assertFirmRestaurant($restaurant);

        return view('firm.restaurants.edit', [
            'title' => 'Restoran düzenle',
            'restaurant' => $restaurant,
            'restaurantAdmin' => RestaurantPrimaryManager::user($restaurant),
        ]);
    }

    public function update(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $this->assertFirmRestaurant($restaurant);

        $message = app(SyncRestaurantPanelSettings::class)->sync($restaurant, $request);

        return redirect()->route('firm.restaurants.index')->with('status', $message);
    }

    private function assertFirmRestaurant(Restaurant $restaurant): void
    {
        if ((int) $restaurant->firm_id !== (int) Auth::user()->firm_id) {
            abort(403);
        }
    }
}
