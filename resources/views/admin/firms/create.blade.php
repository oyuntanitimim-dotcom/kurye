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
        <label class="block text-sm text-slate-600 mb-1">Paket başı kontör (adet)</label>
        <input type="number" step="1" min="1" name="credits_per_order_override" value="{{ old('credits_per_order_override') }}" placeholder="{{ $globalCreditsPerOrder }}" class="w-full rounded border border-slate-300 px-3 py-2">
        <p class="text-xs text-slate-500 mt-1">Her kurye atamasında bu firmadan düşülecek kontör. Boş bırakılırsa genel varsayılan ({{ $globalCreditsPerOrder }} kontör) uygulanır.</p>
        @error('credits_per_order_override')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
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
