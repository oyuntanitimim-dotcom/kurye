<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $restaurant = $this->restaurantForUser();

        return view('restaurant.settings', [
            'title' => 'Ayarlar',
            'restaurant' => $restaurant,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $restaurant = $this->restaurantForUser();

        $request->merge([
            'opening_time' => $request->filled('opening_time') ? $request->input('opening_time') : null,
            'closing_time' => $request->filled('closing_time') ? $request->input('closing_time') : null,
        ]);

        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:500'],
            'opening_time' => ['nullable', 'date_format:H:i'],
            'closing_time' => ['nullable', 'date_format:H:i'],
        ]);

        $restaurant->update($data);

        return redirect()->route('restaurant.settings.edit')->with('status', 'Ayarlar kaydedildi.');
    }

    private function restaurantForUser(): Restaurant
    {
        $restaurant = Restaurant::query()->findOrFail(Auth::user()->restaurant_id);

        return $restaurant;
    }
}
