@extends('layouts.admin')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-semibold">Kontör ayarları</h1>
    <nav class="flex gap-2 text-sm">
        <a href="{{ route('admin.credits.firms') }}" class="rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Firma kontörleri</a>
        <a href="{{ route('admin.credits.purchases') }}" class="rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Talepler</a>
        <a href="{{ route('admin.credits.settings') }}" class="rounded-md bg-panel-accent-soft px-3 py-1.5 font-medium text-slate-900">Ayarlar</a>
    </nav>
</div>

@if(session('status'))
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('admin.credits.settings.update') }}" class="max-w-xl space-y-5 rounded-xl border border-slate-200 bg-white p-6">
    @csrf
    @method('PUT')

    <div>
        <label class="block text-sm font-medium text-slate-700">Kontör birim fiyatı (₺)</label>
        <input type="number" step="0.01" min="0" name="credit_unit_price" value="{{ old('credit_unit_price', number_format($unitPrice, 2, '.', '')) }}"
            class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-panel-accent focus:ring-panel-accent" required>
        <p class="mt-1 text-xs text-slate-500">Firmaların satın aldığı 1 kontörün fiyatı.</p>
        @error('credit_unit_price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Sipariş başı kontör</label>
        <input type="number" step="1" min="1" name="credits_per_order" value="{{ old('credits_per_order', $creditsPerOrder) }}"
            class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-panel-accent focus:ring-panel-accent" required>
        <p class="mt-1 text-xs text-slate-500">Her kurye atamasında firmadan düşülecek kontör (genel varsayılan). Firma bazında özel değer "Firma kontörleri" ekranından verilebilir.</p>
        @error('credits_per_order')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="rounded-lg bg-amber-50 px-4 py-3 text-xs text-amber-800">
        Kontör <strong>kurye ataması anında</strong> düşülür. Kontör biten firma kurye atayamaz (otomatik atama dahil). İptal edilen siparişte iade yapılmaz.
    </div>

    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Kaydet</button>
</form>
@endsection
