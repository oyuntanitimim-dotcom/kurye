<x-finance-online-only-notice />

<form method="get" action="{{ $action }}" class="mb-6 flex flex-wrap gap-3 items-end text-sm">
    @if(isset($couriers) && $couriers->isNotEmpty())
        <x-panel.searchable-select
            name="courier_id"
            label="Kurye"
            placeholder="Kurye ara…"
            :options="collect($couriers)->map(fn($c) => ['value' => $c->id, 'label' => $c->name])->all()"
            :value="($filters['courier_id'] ?? '')"
        />
    @elseif(!empty($restaurants))
        <x-panel.searchable-select
            name="restaurant_id"
            label="İşletme"
            placeholder="İşletme ara…"
            :options="collect($restaurants)->map(fn($r) => ['value' => $r->id, 'label' => $r->name])->all()"
            :value="($filters['restaurant_id'] ?? '')"
        />
    @endif
    <div>
        <label class="block text-slate-600 mb-1">Teslim başlangıç</label>
        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-slate-600 mb-1">Teslim bitiş</label>
        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="rounded border border-slate-300 px-3 py-2">
    </div>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white">Uygula</button>
    <a href="{{ $action }}" class="text-amber-700 hover:underline py-2">Sıfırla</a>
</form>
<p class="text-xs text-slate-500 mb-4 -mt-2">Tüm tutarlar <strong>teslim edilmiş</strong> siparişler üzerinden; tarih filtresi <code class="bg-slate-100 px-1 rounded">updated_at</code> (teslim anı) ile uygulanır.</p>
