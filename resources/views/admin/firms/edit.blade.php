@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Kurye şirketi düzenle</h1>
<form method="post" action="{{ route('admin.firms.update', $firm) }}" enctype="multipart/form-data" class="max-w-xl space-y-4">
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
        <label class="block text-sm text-slate-600 mb-1">Domain</label>
        <input name="domain" value="{{ old('domain', $firm->domain) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Logo dosyası yükle</label>
        <input type="file" name="logo_file" accept="image/*" class="w-full text-sm">
        @error('logo_file')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-slate-500 mt-1">Dosya yüklemek için sunucuda <code class="bg-slate-100 px-1 rounded">php artisan storage:link</code> gerekir.</p>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Logo URL (dosya yoksa)</label>
        <input name="logo" value="{{ old('logo', $firm->logo) }}" class="w-full rounded border border-slate-300 px-3 py-2" placeholder="https://… veya /storage/…">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Paket başı kontör (adet)</label>
        <input type="number" step="1" min="1" name="credits_per_order_override" value="{{ old('credits_per_order_override', $firm->credits_per_order_override) }}" placeholder="{{ $globalCreditsPerOrder }}" class="w-full rounded border border-slate-300 px-3 py-2">
        <p class="text-xs text-slate-500 mt-1">Her kurye atamasında bu firmadan düşülecek kontör. Boş bırakılırsa genel varsayılan ({{ $globalCreditsPerOrder }} kontör) uygulanır.</p>
        @error('credits_per_order_override')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Durum</label>
        <select name="status" class="w-full rounded border border-slate-300 px-3 py-2">
            <option value="active" @selected(old('status', $firm->status)==='active')>Aktif</option>
            <option value="inactive" @selected(old('status', $firm->status)==='inactive')>Pasif</option>
        </select>
    </div>
    <button class="rounded-lg bg-slate-900 px-4 py-2 text-white">Güncelle</button>
</form>
@endsection
