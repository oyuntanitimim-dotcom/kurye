@php
    /** @var \App\Modules\Couriers\Models\Courier|null $courier */
    $courier = $courier ?? null;
    $typeVal = old('compensation_type', $courier !== null ? $courier->compensation_type : 'none');
@endphp
<div class="rounded-lg border border-slate-200 bg-slate-50 p-4 space-y-4">
    <p class="text-sm font-medium text-slate-800">Ücretlendirme</p>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Ödeme modeli</label>
        <select name="compensation_type" class="w-full rounded border border-slate-300 px-3 py-2 bg-white">
            @foreach(\App\Enums\CourierCompensationType::cases() as $ct)
                <option value="{{ $ct->value }}" @selected($typeVal === $ct->value)>{{ $ct->label() }}</option>
            @endforeach
        </select>
        <p class="text-xs text-slate-500 mt-1">Teslim başı ücret seçilirse, sipariş teslim edildiğinde tutar siparişe işlenir. Maaş ve km için tutarlar referans amaçlıdır (bordro / rota hesabı ayrı).</p>
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Teslim başı ücret (₺)</label>
        <input type="number" step="0.01" name="compensation_per_delivery" value="{{ old('compensation_per_delivery', $courier !== null ? $courier->compensation_per_delivery : '') }}" class="w-full max-w-xs rounded border border-slate-300 px-3 py-2 bg-white">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Aylık maaş (₺)</label>
        <input type="number" step="0.01" name="compensation_monthly_salary" value="{{ old('compensation_monthly_salary', $courier !== null ? $courier->compensation_monthly_salary : '') }}" class="w-full max-w-xs rounded border border-slate-300 px-3 py-2 bg-white">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Km başı ücret (₺)</label>
        <input type="number" step="0.0001" name="compensation_per_km" value="{{ old('compensation_per_km', $courier !== null ? $courier->compensation_per_km : '') }}" class="w-full max-w-xs rounded border border-slate-300 px-3 py-2 bg-white">
    </div>
    <div>
        <label class="block text-sm text-slate-600 mb-1">Notlar</label>
        <textarea name="compensation_notes" rows="2" class="w-full rounded border border-slate-300 px-3 py-2 bg-white text-sm" placeholder="Örn. mesai, prim, anlaşma özeti…">{{ old('compensation_notes', $courier !== null ? $courier->compensation_notes : '') }}</textarea>
    </div>
</div>
