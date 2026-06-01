@extends('layouts.firm')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Kontör</h1>

@if(session('status'))
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
@endif

<div class="grid gap-4 md:grid-cols-3 mb-8">
    <div class="rounded-xl border p-6 {{ $balance <= 0 ? 'border-red-200 bg-red-50/60' : 'border-slate-200 bg-white' }}">
        <div class="text-sm text-slate-500">Mevcut kontör</div>
        <div class="text-4xl font-bold tabular-nums {{ $balance <= 0 ? 'text-red-700' : 'text-slate-900' }}">{{ number_format($balance) }}</div>
        @if($balance <= 0)
            <p class="mt-2 text-xs font-medium text-red-700">Kontör bitti — kurye atayamazsınız. Lütfen kontör yükleyin.</p>
        @elseif($balance < $creditsPerOrder * 10)
            <p class="mt-2 text-xs font-medium text-amber-700">Kontör azalıyor.</p>
        @endif
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="text-sm text-slate-500">Sipariş başı kontör</div>
        <div class="text-4xl font-bold tabular-nums">{{ number_format($creditsPerOrder) }}</div>
        <p class="mt-2 text-xs text-slate-500">Her kurye atamasında düşülür.</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="text-sm text-slate-500">Kalan atama (yaklaşık)</div>
        <div class="text-4xl font-bold tabular-nums">{{ $creditsPerOrder > 0 ? number_format(intdiv($balance, $creditsPerOrder)) : '—' }}</div>
        <p class="mt-2 text-xs text-slate-500">Birim fiyat: {{ number_format($unitPrice, 2) }} ₺ / kontör</p>
    </div>
</div>

<div class="mb-8 rounded-xl border border-slate-200 bg-white p-6">
    <h2 class="text-lg font-medium mb-1">Kontör satın al</h2>
    <p class="text-xs text-slate-500 mb-4">Talebiniz yönetici onayından sonra bakiyenize eklenir.</p>

    @if($pendingPurchase)
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Onay bekleyen talebiniz var: <strong>{{ number_format((int) $pendingPurchase->credits) }} kontör</strong>
            ({{ number_format((float) $pendingPurchase->total_price, 2) }} ₺) — {{ $pendingPurchase->created_at->format('d.m.Y H:i') }}
        </div>
    @endif

    <form method="POST" action="{{ route('firm.credits.purchase') }}" class="flex flex-wrap items-end gap-3">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700">Kontör adedi</label>
            <input type="number" min="1" name="credits" id="credit-qty" value="100"
                data-unit="{{ $unitPrice }}"
                class="mt-1 w-40 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400" required>
            @error('credits')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="text-sm text-slate-600">
            Tutar: <span class="font-semibold text-slate-900" id="credit-total">—</span> ₺
        </div>
        <button class="rounded-lg bg-amber-500 px-5 py-2 text-sm font-semibold text-white hover:bg-amber-600">Talep oluştur</button>
    </form>
    <script>
        (function () {
            var qty = document.getElementById('credit-qty');
            var out = document.getElementById('credit-total');
            if (!qty || !out) return;
            var unit = parseFloat(qty.dataset.unit) || 0;
            function render() {
                var n = parseInt(qty.value, 10);
                if (isNaN(n) || n < 0) n = 0;
                out.textContent = (n * unit).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            qty.addEventListener('input', render);
            render();
        })();
    </script>
</div>

<div class="rounded-xl border border-slate-200 bg-white p-6">
    <h2 class="text-lg font-medium mb-4">Kontör hareketleri</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="text-left text-xs uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="py-2 pr-4">Tarih</th>
                    <th class="py-2 pr-4">İşlem</th>
                    <th class="py-2 pr-4">Açıklama</th>
                    <th class="py-2 pr-4 text-right">Değişim</th>
                    <th class="py-2 text-right">Bakiye</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($transactions as $tx)
                    <tr>
                        <td class="py-2 pr-4 text-slate-500">{{ $tx->created_at->format('d.m.Y H:i') }}</td>
                        <td class="py-2 pr-4">{{ $tx->typeLabel() }}</td>
                        <td class="py-2 pr-4 text-slate-500">{{ $tx->description }}</td>
                        <td class="py-2 pr-4 text-right font-semibold tabular-nums {{ $tx->amount < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                            {{ $tx->amount > 0 ? '+' : '' }}{{ number_format($tx->amount) }}
                        </td>
                        <td class="py-2 text-right tabular-nums">{{ number_format($tx->balance_after) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-slate-400">Henüz hareket yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $transactions->links() }}</div>
</div>
@endsection
