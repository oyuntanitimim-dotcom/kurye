@extends('layouts.firm')

@section('content')
<h1 class="text-2xl font-semibold mb-6">{{ $title }}</h1>
@if($errors->any())
    <div class="max-w-xl mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <p class="font-medium mb-1">Kurye güncellenemedi:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form method="post" action="{{ route('firm.couriers.update', $courier) }}" class="max-w-xl space-y-4">
    @csrf
    @method('PUT')
    <div>
        <label class="block text-sm text-slate-600 mb-1">Ad soyad</label>
        <input name="name" value="{{ old('name', $courier->name) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Giriş kullanıcı adı</label>
        <input type="text" name="email" value="{{ old('email', $courier->user?->email) }}" required maxlength="190" autocomplete="username"
            placeholder="ör. ahmet_k veya e-posta"
            class="w-full rounded border border-slate-300 px-3 py-2">
        <p class="text-xs text-slate-500 mt-1">E-posta formatı gerekmez; kurye girişte bu değeri yazar.</p>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Yeni şifre (boş bırakılırsa değişmez)</label>
        <input type="password" name="password" class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Telefon</label>
        <input name="phone" value="{{ old('phone', $courier->phone) }}" class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Araç tipi</label>
        <input name="vehicle_type" value="{{ old('vehicle_type', $courier->vehicle_type) }}" class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Durum</label>
        <select name="status" class="w-full rounded border border-slate-300 px-3 py-2">
            <option value="active" @selected(old('status', $courier->status)==='active')>Aktif</option>
            <option value="inactive" @selected(old('status', $courier->status)==='inactive')>Pasif</option>
        </select>
    </div>
    @error('status')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
    @include('firm.couriers._compensation_fields', ['courier' => $courier])
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Güncelle</button>
    <a href="{{ route('firm.couriers.index') }}" class="ml-2 text-sm text-slate-600 hover:underline">İptal</a>
</form>
@endsection
