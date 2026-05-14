@csrf
<div>
    <label class="block text-sm text-slate-600 mb-1">Kampanya adı</label>
    <input name="name" value="{{ old('name', optional($campaign)->name) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
</div>
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm text-slate-600 mb-1">İndirim %</label>
        <input type="number" step="0.01" name="discount_rate" value="{{ old('discount_rate', optional($campaign)->discount_rate) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Min. sipariş (₺)</label>
        <input type="number" step="0.01" name="min_order" value="{{ old('min_order', optional($campaign)->min_order ?? 0) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
</div>
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm text-slate-600 mb-1">Başlangıç</label>
        <input type="date" name="start_date" value="{{ old('start_date', optional($campaign)->start_date?->format('Y-m-d')) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Bitiş</label>
        <input type="date" name="end_date" value="{{ old('end_date', optional($campaign)->end_date?->format('Y-m-d')) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
</div>
