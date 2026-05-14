<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Restaurants\Actions\SyncRestaurantPanelSettings;
use App\Modules\Firms\Models\Firm;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use App\Support\RestaurantPrimaryManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function index(Request $request): View
    {
        $query = Restaurant::query()->with('firm');

        $firmId = $request->integer('firm_id', 0);
        if ($firmId > 0 && Firm::query()->whereKey($firmId)->exists()) {
            $query->where('firm_id', $firmId);
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('address', 'like', $term);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return view('admin.restaurants', [
            'title' => 'Restoranlar',
            'restaurants' => $query->latest('id')->paginate(40)->appends($request->query()),
            'firms' => Firm::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['firm_id', 'q', 'status']),
        ]);
    }

    public function edit(Restaurant $restaurant): View
    {
        $restaurant->loadMissing('firm');

        return view('admin.restaurants.edit', [
            'title' => 'Restoran düzenle (platform)',
            'restaurant' => $restaurant,
            'restaurantAdmin' => RestaurantPrimaryManager::user($restaurant),
            'adminFirmsForSelect' => Firm::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $request->validate([
            'admin_firm_id' => ['required', 'integer', 'exists:firms,id'],
        ]);

        $newFirmId = (int) $request->input('admin_firm_id');
        $restaurantRoleId = Role::query()->where('name', Role::RESTAURANT)->value('id');

        DB::transaction(function () use ($restaurant, $newFirmId, $restaurantRoleId): void {
            if ($newFirmId !== (int) $restaurant->firm_id) {
                $restaurant->update(['firm_id' => $newFirmId]);
                User::query()
                    ->where('restaurant_id', $restaurant->id)
                    ->where('role_id', $restaurantRoleId)
                    ->update(['firm_id' => $newFirmId]);
            }
        });

        $message = app(SyncRestaurantPanelSettings::class)->sync($restaurant->fresh(), $request);

        return redirect()->route('admin.restaurants.index')->with('status', $message);
    }
}
