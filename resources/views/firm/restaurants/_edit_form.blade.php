<form method="post" action="{{ $formAction }}" class="max-w-xl space-y-4">
    @csrf
    @method('PUT')
    @if(! empty($adminFirmsForSelect))
        <div class="rounded-lg border border-amber-200 bg-amber-50/60 p-4 space-y-2">
            <p class="text-xs font-medium text-amber-950">Platform — kurye şirketi</p>
            <p class="text-xs text-amber-900/90">Restoranı başka bir kurye şirketine taşırsanız, bu işletmenin <strong>restoran paneli kullanıcılarının</strong> bağlı olduğu şirket kaydı da güncellenir.</p>
            <div>
                <label class="block text-sm text-slate-700 mb-1">Kurye şirketi</label>
                <select name="admin_firm_id" required class="w-full rounded border border-amber-300/80 bg-white px-3 py-2">
                    @foreach($adminFirmsForSelect as $f)
                        <option value="{{ $f->id }}" @selected((int) old('admin_firm_id', $restaurant->firm_id) === (int) $f->id)>{{ $f->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif
    <div>
        <label class="block text-sm text-slate-600 mb-1">İşletme türü</label>
        <select name="business_type" required class="w-full rounded border border-slate-300 px-3 py-2">
            @foreach(\App\Enums\RestaurantBusinessType::cases() as $type)
                <option value="{{ $type->value }}" @selected(old('business_type', $restaurant->business_type->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        <p class="text-xs text-slate-500 mt-1">Türü değiştirirseniz mevcut <strong>kategoriler silinir</strong>, yeni türe göre şablon kategorileri oluşturulur. Ürünler listede kalır; çoğu ürünün kategorisi sıfırlanır, panelden yeniden atamanız gerekir.</p>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">İşletme adı</label>
        <input name="name" value="{{ old('name', $restaurant->name) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-slate-600 mb-1">Telefon</label>
            <input name="phone" value="{{ old('phone', $restaurant->phone) }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Durum</label>
            <select name="status" class="w-full rounded border border-slate-300 px-3 py-2">
                <option value="active" @selected(old('status', $restaurant->status)==='active')>Aktif</option>
                <option value="inactive" @selected(old('status', $restaurant->status)==='inactive')>Pasif</option>
            </select>
        </div>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Adres</label>
        <textarea name="address" rows="2" class="w-full rounded border border-slate-300 px-3 py-2">{{ old('address', $restaurant->address) }}</textarea>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-slate-600 mb-1">Enlem</label>
            <input name="latitude" value="{{ old('latitude', $restaurant->latitude) }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Boylam</label>
            <input name="longitude" value="{{ old('longitude', $restaurant->longitude) }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">İşletme paket ücreti (₺/teslim)</label>
        <input type="number" step="0.01" name="fee_per_delivery" value="{{ old('fee_per_delivery', $restaurant->fee_per_delivery) }}" class="w-full max-w-xs rounded border border-slate-300 px-3 py-2">
        <p class="text-xs text-slate-500 mt-1">Boş: kurye şirketi varsayılan teslim başı ücreti.</p>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Mağaza teslim ücreti (₺, sabit)</label>
        <input type="number" step="0.01" name="shop_delivery_fee" value="{{ old('shop_delivery_fee', $restaurant->shop_delivery_fee) }}" class="w-full max-w-xs rounded border border-slate-300 px-3 py-2">
        <p class="text-xs text-slate-500 mt-1">Doldurulursa müşteri mağazasından bu işletmeye verilen siparişlerde <strong>kurye şirketinin mesafe/sabit teslim ücreti ayarlarını yok sayar</strong>; yalnızca bu tutar uygulanır. Boş: kurye şirketi kuralları.</p>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-slate-600 mb-1">Açılış</label>
            <input type="time" name="opening_time" value="{{ old('opening_time', $restaurant->opening_time ? substr((string) $restaurant->opening_time, 0, 5) : '') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Kapanış</label>
            <input type="time" name="closing_time" value="{{ old('closing_time', $restaurant->closing_time ? substr((string) $restaurant->closing_time, 0, 5) : '') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
    </div>

    <hr class="border-slate-200">
    <h2 class="text-sm font-semibold text-slate-800">Restoran paneli girişi</h2>
    <p class="text-xs text-slate-500 -mt-2">İşletme <code class="rounded bg-slate-100 px-1">/restoran</code> paneline bu hesapla giriş yapar. Giriş adresi: <strong>/giris</strong> (ortak giriş sayfası).</p>

    @if($restaurantAdmin)
        <p class="text-xs text-slate-600 rounded-lg border border-amber-100 bg-amber-50/80 px-3 py-2">Şifre veritabanında tek yönlü şifrelenir; mevcut şifre gösterilemez. Unutulduysa veya sıfırlamak için aşağıya yeni şifre yazın.</p>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Yetkili adı soyadı</label>
            <input name="manager_name" value="{{ old('manager_name', $restaurantAdmin->name) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Giriş e-postası</label>
            <input type="email" name="manager_email" value="{{ old('manager_email', $restaurantAdmin->email) }}" required autocomplete="username" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Yeni şifre (isteğe bağlı)</label>
            <input type="password" name="manager_password" value="{{ old('manager_password') }}" autocomplete="new-password" class="w-full rounded border border-slate-300 px-3 py-2" placeholder="Değiştirmeyecekseniz boş bırakın">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Yeni şifre tekrar</label>
            <input type="password" name="manager_password_confirmation" value="{{ old('manager_password_confirmation') }}" autocomplete="new-password" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
    @else
        <p class="text-sm text-amber-900 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">Bu işletmeye bağlı “Restoran” rolünde panel kullanıcısı bulunamadı. Giriş bilgisi güncellemesi için önce kullanıcı oluşturulmalıdır.</p>
    @endif

    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Güncelle</button>
    <a href="{{ $cancelUrl }}" class="ml-2 text-sm text-slate-600 hover:underline">İptal</a>
</form>
