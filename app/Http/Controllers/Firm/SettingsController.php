<?php

namespace App\Http\Controllers\Firm;

use App\Http\Controllers\Controller;
use App\Modules\Firms\Models\Firm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $firm = Firm::query()->findOrFail(Auth::user()->firm_id);

        $defaultJson = $firm->opening_hours !== null
            ? json_encode($firm->opening_hours, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : '';

        $op = $firm->mergedOperationSettings();

        return view('firm.settings', [
            'title' => 'Kurye şirketi ayarları',
            'firm' => $firm,
            'openingHoursJson' => old('opening_hours_json', $defaultJson),
            'autoDispatchEnabled' => old('auto_dispatch_enabled', $op['auto_dispatch_enabled'] ? '1' : '0'),
            'autoAssignBestAfterEta' => old('auto_assign_best_after_eta', ($op['auto_assign_best_after_eta'] ?? false) ? '1' : '0'),
            'locationMaxAgeMinutes' => old('location_max_age_minutes', (string) $op['location_max_age_minutes']),
            'defaultDeliveryFee' => old('default_delivery_fee', (string) $op['default_delivery_fee']),
            'deliveryUseDistance' => old('delivery_use_distance', ($op['delivery_use_distance'] ?? false) ? '1' : '0'),
            'deliveryDistanceBase' => old('delivery_distance_base_fee', (string) $op['delivery_distance_base_fee']),
            'deliveryDistancePerKm' => old('delivery_distance_per_km', (string) $op['delivery_distance_per_km']),
            'deliveryDistanceMin' => old('delivery_distance_min_fee', (string) $op['delivery_distance_min_fee']),
            'deliveryDistanceMax' => old('delivery_distance_max_fee', (string) $op['delivery_distance_max_fee']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $firm = Firm::query()->findOrFail(Auth::user()->firm_id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'city' => ['nullable', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'string', 'max:500'],
            'logo_file' => ['nullable', 'image', 'max:2048'],
            'opening_hours_json' => ['nullable', 'string', 'max:5000'],
            'auto_dispatch_enabled' => ['nullable', 'in:0,1'],
            'auto_assign_best_after_eta' => ['nullable', 'in:0,1'],
            'location_max_age_minutes' => ['nullable', 'integer', 'min:5', 'max:120'],
            'default_delivery_fee' => ['required', 'numeric', 'min:0', 'max:9999'],
            'delivery_use_distance' => ['nullable', 'in:0,1'],
            'delivery_distance_base_fee' => ['required', 'numeric', 'min:0', 'max:9999'],
            'delivery_distance_per_km' => ['required', 'numeric', 'min:0', 'max:500'],
            'delivery_distance_min_fee' => ['required', 'numeric', 'min:0', 'max:9999'],
            'delivery_distance_max_fee' => ['required', 'numeric', 'min:0', 'max:99999', 'gte:delivery_distance_min_fee'],
        ]);

        $openingHours = null;
        $rawHours = trim($data['opening_hours_json'] ?? '');
        if ($rawHours !== '') {
            $decoded = json_decode($rawHours, true);
            if (! is_array($decoded)) {
                return back()->withErrors(['opening_hours_json' => 'Geçerli bir JSON nesnesi girin.'])->withInput();
            }
            $openingHours = $decoded;
        }

        $logoValue = $data['logo'] ?? null;
        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('firm-logos', 'public');
            $logoValue = Storage::url($path);
        }

        $prevSettings = is_array($firm->settings) ? $firm->settings : [];
        $newSettings = array_merge($prevSettings, [
            'auto_dispatch_enabled' => ($data['auto_dispatch_enabled'] ?? '0') === '1',
            'auto_assign_best_after_eta' => ($data['auto_assign_best_after_eta'] ?? '0') === '1',
            'location_max_age_minutes' => isset($data['location_max_age_minutes'])
                ? (int) $data['location_max_age_minutes']
                : (int) ($prevSettings['location_max_age_minutes'] ?? config('courier.dispatch_location_max_age_minutes', 15)),
            'default_delivery_fee' => round((float) $data['default_delivery_fee'], 2),
            'delivery_use_distance' => ($data['delivery_use_distance'] ?? '0') === '1',
            'delivery_distance_base_fee' => round((float) $data['delivery_distance_base_fee'], 2),
            'delivery_distance_per_km' => round((float) $data['delivery_distance_per_km'], 2),
            'delivery_distance_min_fee' => round((float) $data['delivery_distance_min_fee'], 2),
            'delivery_distance_max_fee' => round((float) $data['delivery_distance_max_fee'], 2),
        ]);

        $firm->update([
            'name' => $data['name'],
            'city' => $data['city'] ?? null,
            'district' => $data['district'] ?? null,
            'logo' => $logoValue,
            'opening_hours' => $openingHours,
            'settings' => $newSettings,
        ]);

        return redirect()->route('firm.settings.edit')->with('status', 'Ayarlar kaydedildi.');
    }
}
