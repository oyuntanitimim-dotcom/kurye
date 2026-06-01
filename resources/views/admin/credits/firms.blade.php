@extends('layouts.admin')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-semibold">Firma kontörleri</h1>
    <nav class="flex gap-2 text-sm">
        <a href="{{ route('admin.credits.firms') }}" class="rounded-md bg-panel-accent-soft px-3 py-1.5 font-medium text-slate-900">Firma kontörleri</a>
        <a href="{{ route('admin.credits.purchases') }}" class="rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Talepler</a>
        <a href="{{ route('admin.credits.settings') }}" class="rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Ayarlar</a>
    </nav>
</div>

@if(session('status'))
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
@endif

<form method="GET" class="mb-4 flex gap-2">
    <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Firma ara..."
        class="w-64 rounded-lg border-slate-300 text-sm focus:border-panel-accent focus:ring-panel-accent">
    <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Ara</button>
</form>

<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-4 py-3">Firma</th>
                <th class="px-4 py-3 text-right">Bakiye (kontör)</th>
                <th class="px-4 py-3 text-right">Sipariş başı</th>
                <th class="px-4 py-3">Kontör yükle / düş</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($firms as $firm)
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-800">{{ $firm->name }}</div>
                        <div class="text-xs text-slate-400">#{{ $firm->id }} · {{ $firm->city }}</div>
                        <a href="{{ route('admin.credits.firm.history', $firm) }}" class="text-xs font-medium text-amber-700 hover:underline">Hareketler →</a>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <span class="rounded-md px-2 py-0.5 font-semibold tabular-nums {{ (int) $firm->credit_balance <= 0 ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                            {{ number_format((int) $firm->credit_balance) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('admin.credits.firm.update', $firm) }}" class="flex items-center justify-end gap-1">
                            @csrf @method('PUT')
                            <input type="number" min="1" name="credits_per_order_override" value="{{ $firm->credits_per_order_override }}"
                                placeholder="{{ $globalCreditsPerOrder }}"
                                class="w-20 rounded-md border-slate-300 text-right text-xs">
                            <button class="rounded-md border border-slate-300 px-2 py-1 text-xs hover:bg-slate-50">Kaydet</button>
                        </form>
                        <div class="mt-0.5 text-[11px] text-slate-400">boş = genel ({{ $globalCreditsPerOrder }})</div>
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('admin.credits.adjust', $firm) }}" class="flex flex-wrap items-center gap-1">
                            @csrf
                            <input type="number" name="amount" placeholder="+/- adet" required
                                class="w-24 rounded-md border-slate-300 text-xs">
                            <input type="text" name="description" placeholder="açıklama"
                                class="w-40 rounded-md border-slate-300 text-xs">
                            <button class="rounded-md bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800">Uygula</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Firma bulunamadı.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $firms->links() }}</div>
@endsection
