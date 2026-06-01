@extends('layouts.admin')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-semibold">Kontör talepleri</h1>
    <nav class="flex gap-2 text-sm">
        <a href="{{ route('admin.credits.firms') }}" class="rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Firma kontörleri</a>
        <a href="{{ route('admin.credits.purchases') }}" class="rounded-md bg-panel-accent-soft px-3 py-1.5 font-medium text-slate-900">Talepler @if($pendingCount > 0)<span class="ml-1 rounded-full bg-red-600 px-1.5 text-xs text-white">{{ $pendingCount }}</span>@endif</a>
        <a href="{{ route('admin.credits.settings') }}" class="rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Ayarlar</a>
    </nav>
</div>

@if(session('status'))
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
@endif

<div class="mb-4 flex gap-2 text-sm">
    @php $sf = $filters['status']; @endphp
    <a href="{{ route('admin.credits.purchases') }}" class="rounded-md px-3 py-1.5 {{ $sf === '' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Tümü</a>
    <a href="{{ route('admin.credits.purchases', ['status' => 'pending']) }}" class="rounded-md px-3 py-1.5 {{ $sf === 'pending' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Onay bekleyen</a>
    <a href="{{ route('admin.credits.purchases', ['status' => 'approved']) }}" class="rounded-md px-3 py-1.5 {{ $sf === 'approved' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Onaylanan</a>
    <a href="{{ route('admin.credits.purchases', ['status' => 'rejected']) }}" class="rounded-md px-3 py-1.5 {{ $sf === 'rejected' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Reddedilen</a>
</div>

<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-4 py-3">Tarih</th>
                <th class="px-4 py-3">Firma</th>
                <th class="px-4 py-3 text-right">Kontör</th>
                <th class="px-4 py-3 text-right">Tutar</th>
                <th class="px-4 py-3">Durum</th>
                <th class="px-4 py-3 text-right">İşlem</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($purchases as $p)
                <tr>
                    <td class="px-4 py-3 text-slate-500">{{ $p->created_at->format('d.m.Y H:i') }}</td>
                    <td class="px-4 py-3 font-medium text-slate-800">{{ $p->firm?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format((int) $p->credits) }}</td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format((float) $p->total_price, 2) }} ₺</td>
                    <td class="px-4 py-3">
                        @php
                            $cls = match($p->status) {
                                'pending' => 'bg-amber-50 text-amber-700',
                                'approved' => 'bg-emerald-50 text-emerald-700',
                                'rejected' => 'bg-red-50 text-red-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $cls }}">{{ $p->statusLabel() }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($p->status === 'pending')
                            <div class="flex justify-end gap-1">
                                <form method="POST" action="{{ route('admin.credits.purchases.approve', $p) }}">
                                    @csrf
                                    <button class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">Onayla</button>
                                </form>
                                <form method="POST" action="{{ route('admin.credits.purchases.reject', $p) }}">
                                    @csrf
                                    <button class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">Reddet</button>
                                </form>
                            </div>
                        @else
                            <span class="text-xs text-slate-400">{{ $p->approved_at?->format('d.m.Y H:i') }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Talep yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $purchases->links() }}</div>
@endsection
