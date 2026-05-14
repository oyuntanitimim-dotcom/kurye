@extends('layouts.firm')

@section('content')
<h1 class="text-2xl font-semibold mb-6">{{ $title }}</h1>
<form method="post" action="{{ route('firm.restaurants.store') }}" class="max-w-xl space-y-4">
    @csrf
    <div>
        <label class="block text-sm text-slate-600 mb-1">İşletme türü</label>
        <select name="business_type" required class="w-full rounded border border-slate-300 px-3 py-2">
            @foreach(\App\Enums\RestaurantBusinessType::cases() as $type)
                <option value="{{ $type->value }}" @selected(old('business_type', 'restaurant') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        <p class="text-xs text-slate-500 mt-1">Türe göre satış menüsü için varsayılan kategoriler oluşturulur. Türü daha sonra restoran düzenlemeden de değiştirebilirsiniz; değişince kategoriler yenilenir.</p>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">İşletme adı</label>
        <input name="name" value="{{ old('name') }}" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-slate-600 mb-1">Telefon</label>
            <input name="phone" value="{{ old('phone') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Durum</label>
            <select name="status" class="w-full rounded border border-slate-300 px-3 py-2">
                <option value="active" @selected(old('status', 'active')==='active')>Aktif</option>
                <option value="inactive" @selected(old('status')==='inactive')>Pasif</option>
            </select>
        </div>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Adres</label>
        <textarea name="address" rows="2" class="w-full rounded border border-slate-300 px-3 py-2">{{ old('address') }}</textarea>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-slate-600 mb-1">Enlem</label>
            <input name="latitude" value="{{ old('latitude') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Boylam</label>
            <input name="longitude" value="{{ old('longitude') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">İşletme paket ücreti (₺/teslim)</label>
        <input type="number" step="0.01" name="fee_per_delivery" value="{{ old('fee_per_delivery') }}" class="w-full max-w-xs rounded border border-slate-300 px-3 py-2">
        <p class="text-xs text-slate-500 mt-1">Boş bırakılırsa kurye şirketinin varsayılan teslim başı ücreti uygulanır.</p>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Mağaza teslim ücreti (₺, sabit)</label>
        <input type="number" step="0.01" name="shop_delivery_fee" value="{{ old('shop_delivery_fee') }}" class="w-full max-w-xs rounded border border-slate-300 px-3 py-2">
        <p class="text-xs text-slate-500 mt-1">İsteğe bağlı. Doluysa mağaza siparişlerinde yalnızca bu teslim ücreti kullanılır.</p>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-slate-600 mb-1">Açılış</label>
            <input type="time" name="opening_time" value="{{ old('opening_time') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Kapanış</label>
            <input type="time" name="closing_time" value="{{ old('closing_time') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
    </div>
    <hr class="border-slate-200">
    <p class="text-sm font-medium text-slate-700">Restoran yöneticisi</p>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Ad soyad</label>
        <input name="admin_name" value="{{ old('admin_name') }}" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">E-posta</label>
        <input type="email" name="admin_email" value="{{ old('admin_email') }}" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Şifre</label>
        <input type="password" name="admin_password" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Kaydet</button>
    <a href="{{ route('firm.restaurants.index') }}" class="ml-2 text-sm text-slate-600 hover:underline">İptal</a>
</form>
@endsection
