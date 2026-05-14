@extends('layouts.firm')

@section('content')
<h1 class="text-2xl font-semibold mb-6">{{ $title }}</h1>
<p class="text-sm text-slate-600 mb-4">Paket başı platform ücreti ve işletmelere uygulanacak varsayılan teslim başı ücreti yalnızca platform yöneticisi tarafından değiştirilir. İşletme bazlı ücreti kurye şirketi panelinden düzenleyebilirsiniz.</p>
<form method="post" action="{{ route('firm.settings.update') }}" enctype="multipart/form-data" class="max-w-xl space-y-4">
    @csrf
    @method('PUT')
    <div>
        <label class="block text-sm text-slate-600 mb-1">Kurye şirketi adı</label>
        <input name="name" value="{{ old('name', $firm->name) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-slate-600 mb-1">Şehir</label>
            <input name="city" value="{{ old('city', $firm->city) }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">İlçe</label>
            <input name="district" value="{{ old('district', $firm->district) }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Logo dosyası yükle</label>
        <input type="file" name="logo_file" accept="image/*" class="w-full text-sm">
        @error('logo_file')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-slate-500 mt-1">Yükleme için sunucuda <code class="bg-slate-100 px-1">php artisan storage:link</code> gerekir.</p>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Logo URL (dosya yoksa)</label>
        <input name="logo" value="{{ old('logo', $firm->logo) }}" class="w-full rounded border border-slate-300 px-3 py-2" placeholder="https://... veya /storage/...">
    </div>
    @if($firm->logo)
        @php
            $logoSrc = filter_var($firm->logo, FILTER_VALIDATE_URL)
                ? $firm->logo
                : (str_starts_with($firm->logo, '/') ? url($firm->logo) : asset('storage/'.$firm->logo));
        @endphp
        <p class="text-xs text-slate-600">Önizleme: <img src="{{ $logoSrc }}" alt="" class="mt-1 h-12 object-contain"></p>
    @endif
    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 space-y-3">
        <p class="text-sm font-medium text-slate-800">Operasyon</p>
        <input type="hidden" name="auto_dispatch_enabled" value="0">
        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
            <input type="checkbox" name="auto_dispatch_enabled" value="1" @if($autoDispatchEnabled === '1') checked @endif class="rounded border-slate-300">
            Siparişler / Operasyon ekranında «Otomatik ata» için skorlama (mesafe + aktif yük + konum tazeliği). Restoran «Kurye çağır» dediğinde sipariş doğrudan atanmaz; kuyrukta bekler.
        </label>
        <input type="hidden" name="auto_assign_best_after_eta" value="0">
        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
            <input type="checkbox" name="auto_assign_best_after_eta" value="1" @if(($autoAssignBestAfterEta ?? '0') === '1') checked @endif class="rounded border-slate-300">
            Operasyon (mobil): ETA sonrası «en hızlı kurye»yi otomatik ata
        </label>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Konum tazelik süresi (dakika)</label>
            <input type="number" name="location_max_age_minutes" value="{{ $locationMaxAgeMinutes }}" min="5" max="120" class="w-32 rounded border border-slate-300 px-3 py-2 text-sm">
            <p class="text-xs text-slate-500 mt-1">Kurye konumu bu süreden eskiyse ceza uygulanır; çok eski konumlar dışlanır.</p>
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Mağaza teslimat ücreti — sabit (₺)</label>
            <input type="number" name="default_delivery_fee" value="{{ $defaultDeliveryFee }}" min="0" step="0.01" class="w-40 rounded border border-slate-300 px-3 py-2 text-sm" required>
            <p class="text-xs text-slate-500 mt-1">Mesafe kapalıyken veya adres/restoran koordinatı yokken kullanılır.</p>
        </div>
        <input type="hidden" name="delivery_use_distance" value="0">
        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
            <input type="checkbox" name="delivery_use_distance" value="1" @if($deliveryUseDistance === '1') checked @endif class="rounded border-slate-300">
            Mesafeye göre teslim ücreti (restoran + müşteri adresi enlem/boylam gerekir)
        </label>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div>
                <label class="block text-xs text-slate-600 mb-1">Mesafe taban (₺)</label>
                <input type="number" name="delivery_distance_base_fee" value="{{ $deliveryDistanceBase }}" min="0" step="0.01" class="w-full rounded border border-slate-300 px-2 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Km başı (₺)</label>
                <input type="number" name="delivery_distance_per_km" value="{{ $deliveryDistancePerKm }}" min="0" step="0.01" class="w-full rounded border border-slate-300 px-2 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Min (₺)</label>
                <input type="number" name="delivery_distance_min_fee" value="{{ $deliveryDistanceMin }}" min="0" step="0.01" class="w-full rounded border border-slate-300 px-2 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Max (₺)</label>
                <input type="number" name="delivery_distance_max_fee" value="{{ $deliveryDistanceMax }}" min="0" step="0.01" class="w-full rounded border border-slate-300 px-2 py-2 text-sm" required>
            </div>
        </div>
        <p class="text-xs text-slate-500">Formül (açıkken): max(min, min(max, taban + km × km başı)). Koordinat eksikse sabit ücret uygulanır.</p>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Çalışma saatleri (JSON)</label>
        <textarea name="opening_hours_json" rows="6" class="w-full rounded border border-slate-300 px-3 py-2 font-mono text-sm" placeholder='{"mon":["09:00","23:00"]}'>{{ $openingHoursJson }}</textarea>
        @error('opening_hours_json')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Kaydet</button>
</form>
@endsection
