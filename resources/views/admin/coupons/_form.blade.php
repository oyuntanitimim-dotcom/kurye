@csrf
<div>
    <label class="block text-sm text-slate-600 mb-1">Kurye şirketi</label>
    <select name="firm_id" required class="w-full rounded border border-slate-300 px-3 py-2">
        @foreach($firms as $f)
            <option value="{{ $f->id }}" @selected(old('firm_id', optional($coupon)->firm_id) == $f->id)>{{ $f->name }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="block text-sm text-slate-600 mb-1">Kod</label>
    <input name="code" value="{{ old('code', optional($coupon)->code) }}" required class="w-full rounded border border-slate-300 px-3 py-2 font-mono uppercase">
</div>
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm text-slate-600 mb-1">İndirim (₺)</label>
        <input type="number" step="0.01" name="discount" value="{{ old('discount', optional($coupon)->discount) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Kullanım limiti</label>
        <input type="number" name="usage_limit" value="{{ old('usage_limit', optional($coupon)->usage_limit ?? 1) }}" required min="1" class="w-full rounded border border-slate-300 px-3 py-2">
    </div>
</div>
<div>
    <label class="block text-sm text-slate-600 mb-1">Son kullanma</label>
    <input type="date" name="expire_date" value="{{ old('expire_date', optional($coupon)->expire_date?->format('Y-m-d')) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
</div>
@if(optional($coupon)->id)
    <p class="text-sm text-slate-500">Kullanım sayısı: <strong>{{ $coupon->used_count }}</strong> (salt okunur)</p>
@endif
