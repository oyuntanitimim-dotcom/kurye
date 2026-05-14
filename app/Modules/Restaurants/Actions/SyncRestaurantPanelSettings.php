<?php

declare(strict_types=1);

namespace App\Modules\Restaurants\Actions;

use App\Enums\RestaurantBusinessType;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Restaurants\Services\RestaurantMenuTemplateService;
use App\Support\RestaurantPrimaryManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

final class SyncRestaurantPanelSettings
{
    public function __construct(
        private RestaurantMenuTemplateService $menuTemplate,
    ) {}

    public function sync(Restaurant $restaurant, Request $request): string
    {
        $request->merge([
            'opening_time' => $request->filled('opening_time') ? $request->input('opening_time') : null,
            'closing_time' => $request->filled('closing_time') ? $request->input('closing_time') : null,
            'shop_delivery_fee' => $request->filled('shop_delivery_fee') ? $request->input('shop_delivery_fee') : null,
        ]);

        $manager = RestaurantPrimaryManager::user($restaurant);

        $rules = [
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
        ];

        if ($manager !== null) {
            $rules['manager_name'] = ['required', 'string', 'max:190'];
            $rules['manager_email'] = ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($manager->id)];
            $rules['manager_password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }

        $data = $request->validate($rules);

        $businessType = $data['business_type'] instanceof RestaurantBusinessType
            ? $data['business_type']
            : RestaurantBusinessType::from((string) $data['business_type']);

        $previousType = $restaurant->business_type;
        $typeChanged = $previousType !== $businessType;

        if ($typeChanged) {
            $restaurant->categories()->delete();
        }

        $payload = $data;
        unset($payload['business_type'], $payload['manager_name'], $payload['manager_email'], $payload['manager_password'], $payload['manager_password_confirmation']);
        $payload['business_type'] = $businessType;

        if (array_key_exists('fee_per_delivery', $payload) && ($payload['fee_per_delivery'] === '' || $payload['fee_per_delivery'] === null)) {
            $payload['fee_per_delivery'] = null;
        }

        if (array_key_exists('shop_delivery_fee', $payload) && ($payload['shop_delivery_fee'] === '' || $payload['shop_delivery_fee'] === null)) {
            $payload['shop_delivery_fee'] = null;
        }

        $restaurant->update($payload);

        if ($typeChanged) {
            $this->menuTemplate->apply($restaurant->fresh(), $businessType);
        }

        if ($manager !== null) {
            $manager->name = $data['manager_name'];
            $manager->email = $data['manager_email'];
            if (! empty($data['manager_password'])) {
                $manager->password = Hash::make($data['manager_password']);
            }
            $manager->save();
        }

        $passwordReset = $manager !== null && ! empty($data['manager_password'] ?? null);

        if ($typeChanged) {
            $msg = 'Restoran güncellendi. İşletme türü değişti; kategoriler yeni şablona göre yenilendi. Ürünlerin kategori atamalarını kontrol edin.';
            if ($passwordReset) {
                $msg .= ' Restoran paneli şifresi de güncellendi.';
            }

            return $msg;
        }

        if ($passwordReset) {
            return 'Restoran güncellendi. Restoran paneli (/restoran) giriş şifresi güncellendi.';
        }

        return 'Restoran güncellendi.';
    }
}
