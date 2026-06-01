@extends('layouts.admin')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-semibold">{{ $firm->name }}</h1>
    <div class="flex flex-wrap gap-3 text-sm">
        <a href="{{ route('admin.firms.index') }}" class="text-slate-600 hover:underline">← Şirketler</a>
        <a href="{{ route('admin.firms.edit', $firm) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-slate-800 hover:bg-slate-50">Düzenle</a>
        <a href="{{ route('admin.restaurants.index', ['firm_id' => $firm->id]) }}" class="rounded-lg bg-slate-900 px-3 py-1.5 text-white hover:bg-slate-800">Restoranlar ({{ $restaurantCount }})</a>
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 max-w-3xl text-sm">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-slate-500 text-xs uppercase tracking-wide">Domain</p>
        <p class="font-medium mt-1">{{ $firm->domain }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-slate-500 text-xs uppercase tracking-wide">Durum</p>
        <p class="font-medium mt-1">{{ $firm->status }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-slate-500 text-xs uppercase tracking-wide">Paket başı kontör</p>
        <p class="font-medium mt-1">{{ $firm->credits_per_order_override ?? 'Genel varsayılan' }} @if($firm->credits_per_order_override) kontör @endif</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-slate-500 text-xs uppercase tracking-wide">Kontör bakiyesi</p>
        <p class="font-medium mt-1 {{ (int) $firm->credit_balance <= 0 ? 'text-red-600' : '' }}">{{ number_format((int) $firm->credit_balance) }} kontör</p>
    </div>
    @if($firm->city || $firm->district)
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:col-span-2">
            <p class="text-slate-500 text-xs uppercase tracking-wide">Konum</p>
            <p class="font-medium mt-1">{{ trim(implode(' / ', array_filter([$firm->city, $firm->district]))) ?: '—' }}</p>
        </div>
    @endif
</div>
@endsection
