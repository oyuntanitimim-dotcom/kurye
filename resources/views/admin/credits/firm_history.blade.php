@extends('layouts.admin')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('admin.credits.firms') }}" class="text-sm text-slate-500 hover:underline">← Firma kontörleri</a>
        <h1 class="text-2xl font-semibold">{{ $firm->name }} — Kontör hareketleri</h1>
        <p class="text-sm text-slate-500">#{{ $firm->id }} · {{ $firm->city }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-right">
        <div class="text-xs text-slate-500">Mevcut bakiye</div>
        <div class="text-2xl font-bold tabular-nums {{ (int) $firm->credit_balance <= 0 ? 'text-red-600' : 'text-emerald-700' }}">{{ number_format((int) $firm->credit_balance) }}</div>
        <div class="text-xs text-slate-400">sipariş başı {{ $creditsPerOrder }} kontör</div>
    </div>
</div>

<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
    <div class="flex gap-2 text-sm">
        @php $tf = $filters['type']; @endphp
        <a href="{{ route('admin.credits.firm.history', $firm) }}" class="rounded-md px-3 py-1.5 {{ $tf === '' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Tümü</a>
        <a href="{{ route('admin.credits.firm.history', [$firm, 'type' => 'purchase']) }}" class="rounded-md px-3 py-1.5 {{ $tf === 'purchase' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Satın alma</a>
        <a href="{{ route('admin.credits.firm.history', [$firm, 'type' => 'admin_adjustment']) }}" class="rounded-md px-3 py-1.5 {{ $tf === 'admin_adjustment' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Yönetici</a>
        <a href="{{ route('admin.credits.firm.history', [$firm, 'type' => 'order_deduction']) }}" class="rounded-md px-3 py-1.5 {{ $tf === 'order_deduction' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Sipariş düşümü</a>
    </div>
    <form method="POST" action="{{ route('admin.credits.adjust', $firm) }}" class="flex flex-wrap items-center gap-1">
        @csrf
        <input type="number" name="amount" placeholder="+/- adet" required class="w-24 rounded-md border-slate-300 text-xs">
        <input type="text" name="description" placeholder="açıklama" class="w-40 rounded-md border-slate-300 text-xs">
        <button class="rounded-md bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800">Kontör yükle / düş</button>
    </form>
</div>

@if(session('status'))
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
@endif

<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-4 py-3">Tarih / saat</th>
                <th class="px-4 py-3">İşlem</th>
                <th class="px-4 py-3">Açıklama</th>
                <th class="px-4 py-3">İşlemi yapan</th>
                <th class="px-4 py-3 text-right">Değişim</th>
                <th class="px-4 py-3 text-right">Bakiye</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($transactions as $tx)
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $tx->created_at->format('d.m.Y H:i:s') }}</td>
                    <td class="px-4 py-3">
                        @php
                            $cls = match($tx->type) {
                                'purchase' => 'bg-emerald-50 text-emerald-700',
                                'admin_adjustment' => 'bg-indigo-50 text-indigo-700',
                                'order_deduction' => 'bg-slate-100 text-slate-600',
                                'refund' => 'bg-amber-50 text-amber-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $cls }}">{{ $tx->typeLabel() }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-500">
                        {{ $tx->description }}
                        @if($tx->order_id)<a href="{{ route('admin.orders.show', $tx->order_id) }}" class="text-amber-700 hover:underline">#{{ $tx->order_id }}</a>@endif
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $tx->creator?->name ?? ($tx->type === 'order_deduction' ? 'Sistem (otomatik)' : '—') }}</td>
                    <td class="px-4 py-3 text-right font-semibold tabular-nums {{ $tx->amount < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                        {{ $tx->amount > 0 ? '+' : '' }}{{ number_format($tx->amount) }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($tx->balance_after) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Henüz hareket yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $transactions->links() }}</div>
@endsection
