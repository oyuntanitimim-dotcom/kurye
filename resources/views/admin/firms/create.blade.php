@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Yeni kurye şirketi</h1>
<form method="post" action="{{ route('admin.firms.store') }}" class="max-w-xl space-y-4">
    @csrf
    <div>
        <label class="block text-sm text-slate-600 mb-1">Kurye şirketi adı</label>
        <input name="name" value="{{ old('name') }}" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-slate-600 mb-1">Şehir</label>
            <input name="city" value="{{ old('city') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">İlçe</label>
            <input name="district" value="{{ old('district') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Domain (ör. localhost)</label>
        <input name="domain" value="{{ old('domain') }}" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Platform paket başı ücret (₺)</label>
        <input type="number" step="0.01" name="platform_fee_per_order" value="{{ old('platform_fee_per_order', 0) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
        <p class="text-xs text-slate-500 mt-1">Teslim edilen her sipariş için platforma ödenecek sabit tutar.</p>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Varsayılan işletme paket ücreti (₺/teslim)</label>
        <input type="number" step="0.01" name="default_restaurant_fee_per_delivery" value="{{ old('default_restaurant_fee_per_delivery', 0) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
        <p class="text-xs text-slate-500 mt-1">İşletmede özel tutar yoksa her teslim için bu sabit ücret uygulanır.</p>
    </div>
    <hr class="border-slate-200">
    <p class="text-sm font-medium text-slate-700">Kurye şirketi yöneticisi</p>
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
    <button class="rounded-lg bg-slate-900 px-4 py-2 text-white">Kaydet</button>
</form>
@endsection
