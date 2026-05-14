@php use App\Enums\OrderStatus; @endphp
@extends('layouts.restaurant')

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <h1 class="text-2xl font-semibold text-slate-900">Tüm siparişler</h1>
    <button type="button" id="btn-open-new-order" class="rounded-lg bg-panel-accent px-4 py-2 text-sm font-medium text-white hover:opacity-95">
        Yeni sipariş
    </button>
</div>

<form method="get" action="{{ route('restaurant.orders.index') }}" class="mb-6 space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <x-panel.table-toolbar
        search-placeholder="Sipariş no veya müşteri ara…"
        :search-value="old('q', $filters['q'] ?? '')"
        :per-page-options="[10, 25, 30, 50]"
        :current-per-page="(int) ($filters['per_page'] ?? 25)"
    />
    @if(!empty($openNewOrderModal))
        <input type="hidden" name="open_new_order" value="1">
    @endif
</form>

<div class="overflow-x-auto text-sm rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full">
        <thead><tr class="text-left border-b border-slate-100 text-slate-500"><th class="py-2 px-4">#</th><th class="py-2 px-4">Kanal</th><th class="py-2 px-4">Müşteri</th><th class="py-2 px-4">Ödeme</th><th class="py-2 px-4">Durum</th><th class="py-2 px-4">Tutar</th></tr></thead>
        <tbody>
            @forelse($orders as $o)
                <tr class="border-b border-slate-100 hover:bg-slate-50/80">
                    <td class="py-2 px-4"><a href="{{ route('restaurant.orders.show', $o) }}" data-open-modal data-modal-title="Sipariş #{{ $o->id }}" data-modal-subtitle="Sipariş detayları" class="font-medium text-panel-accent hover:underline">#{{ $o->id }}</a></td>
                    <td class="py-2 px-4">
                        @if($o->sourceEnum())
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs">{{ $o->sourceEnum()->label() }}</span>
                            @if($o->marketplace_provider)
                                <span class="text-xs text-slate-500">· {{ $o->marketplace_provider }}</span>
                            @endif
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="py-2 px-4">{{ $o->customerDisplayName() }}</td>
                    <td class="py-2 px-4 text-slate-700">{{ $o->paymentMethodLabel() }}</td>
                    <td class="py-2 px-4">{{ OrderStatus::tryFrom($o->status)?->label() ?? $o->status }}</td>
                    <td class="py-2 px-4">{{ number_format((float) $o->total_price, 2) }} ₺</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-slate-500">Kayıt bulunamadı.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>

@include('restaurant.orders.partials.new-order-modal', ['products' => $products])

@push('scripts')
<script>
(function () {
    var dlg = document.getElementById('restaurant-new-order-dialog');
    if (!dlg) return;

    function openDlg() {
        if (typeof dlg.showModal === 'function') dlg.showModal();
    }
    function closeDlg() {
        if (typeof dlg.close === 'function') dlg.close();
    }

    var btn = document.getElementById('btn-open-new-order');
    if (btn) btn.addEventListener('click', openDlg);

    document.querySelectorAll('[data-close-new-order]').forEach(function (el) {
        el.addEventListener('click', closeDlg);
    });

    dlg.addEventListener('click', function (ev) {
        if (ev.target === dlg) closeDlg();
    });

    @if(!empty($openNewOrderModal))
    openDlg();
    @endif
})();
</script>
@endpush
@endsection
