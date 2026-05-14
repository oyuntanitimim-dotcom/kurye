@extends('layouts.firm')

@section('content')
<h1 class="text-2xl font-semibold mb-6">{{ $title }}</h1>
<form method="post" action="{{ route('firm.couriers.store') }}" class="max-w-xl space-y-4">
    @csrf
    <div>
        <label class="block text-sm text-slate-600 mb-1">Ad soyad</label>
        <input name="name" value="{{ old('name') }}" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Giriş kullanıcı adı</label>
        <input type="text" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="username"
            placeholder="ör. ahmet_k, nick veya e-posta"
            class="w-full rounded border border-slate-300 px-3 py-2">
        <p class="text-xs text-slate-500 mt-1">E-posta zorunlu değil; benzersiz bir kullanıcı adı, takma ad veya isim kullanılabilir. Giriş ekranında aynı değer kullanılır.</p>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Şifre</label>
        <input type="password" name="password" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Telefon</label>
        <input name="phone" value="{{ old('phone') }}" class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Araç tipi</label>
        <input name="vehicle_type" value="{{ old('vehicle_type') }}" placeholder="ör. motosiklet" class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Durum</label>
        <select name="status" class="w-full rounded border border-slate-300 px-3 py-2">
            <option value="active" @selected(old('status', 'active')==='active')>Aktif</option>
            <option value="inactive" @selected(old('status')==='inactive')>Pasif</option>
        </select>
    </div>
    @include('firm.couriers._compensation_fields', ['courier' => null])
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Kaydet</button>
    <a href="{{ route('firm.couriers.index') }}" class="ml-2 text-sm text-slate-600 hover:underline">İptal</a>
</form>
@endsection
