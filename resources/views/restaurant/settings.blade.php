@extends('layouts.restaurant')

@section('content')
<h1 class="text-2xl font-semibold mb-6">{{ $title }}</h1>
<div class="rounded-xl border border-slate-200 bg-white p-4 mb-6 max-w-xl">
    <p class="text-sm text-slate-600">İşletme</p>
    <p class="text-lg font-semibold">{{ $restaurant->name }}</p>
    <p class="text-sm text-slate-500 mt-1">Durum: {{ $restaurant->status }}</p>
</div>
<form method="post" action="{{ route('restaurant.settings.update') }}" class="max-w-xl space-y-4">
    @csrf
    @method('PUT')
    <div>
        <label class="block text-sm text-slate-600 mb-1">Telefon</label>
        <input name="phone" value="{{ old('phone', $restaurant->phone) }}" class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Adres</label>
        <textarea name="address" rows="2" class="w-full rounded border border-slate-300 px-3 py-2">{{ old('address', $restaurant->address) }}</textarea>
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
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Kaydet</button>
</form>
@endsection
