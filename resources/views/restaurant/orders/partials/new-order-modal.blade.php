<dialog id="restaurant-new-order-dialog" class="w-full max-w-6xl rounded-2xl border border-slate-200 bg-white p-0 shadow-xl backdrop:bg-black/40">
    <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between gap-4 shrink-0">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Yeni sipariş</h2>
            <p class="text-sm text-slate-600 mt-0.5">Telefon veya dükkândan gelen siparişi girin.</p>
        </div>
        <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800" aria-label="Kapat" data-close-new-order>
            <span class="text-xl leading-none">&times;</span>
        </button>
    </div>
    <form method="post" action="{{ route('restaurant.orders.manual.store') }}" class="flex flex-col max-h-[min(88vh,720px)]">
        @csrf
        <input type="hidden" name="_form" value="new_order">

        {{-- Grid: sol sabit ~180px; flex+w-full kullanımı sol paneli %90 yapıyordu --}}
        <div class="grid grid-cols-1 lg:grid-cols-[180px_minmax(0,1fr)] gap-5 lg:gap-6 px-6 py-4 overflow-y-auto min-h-0 flex-1">
            {{-- Sol: sabit dar sütun — sipariş bilgileri --}}
            <div class="min-w-0 space-y-2.5">
                <h3 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2">Sipariş bilgileri</h3>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Kaynak</label>
                    <select name="source" class="rounded-lg border border-slate-300 px-2.5 py-1.5 w-full text-sm" required>
                        <option value="phone" @selected(old('source', 'phone') === 'phone')>Telefon</option>
                        <option value="walk_in" @selected(old('source') === 'walk_in')>Dükkân / gel-al</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Müşteri adı</label>
                    <input type="text" name="customer_name" value="{{ old('customer_name') }}" required maxlength="190"
                        class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Telefon</label>
                    <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" required maxlength="48"
                        class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Ödeme</label>
                    <select name="payment_method" class="rounded-lg border border-slate-300 px-2.5 py-1.5 w-full text-sm" required>
                        <option value="cash_on_delivery" @selected(old('payment_method') === 'cash_on_delivery')>Kapıda nakit</option>
                        <option value="card_on_delivery" @selected(old('payment_method') === 'card_on_delivery')>Kapıda kart</option>
                        <option value="online" @selected(old('payment_method') === 'online')>Online</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Teslimat ücreti (₺)</label>
                    <input type="number" name="delivery_fee" value="{{ old('delivery_fee', '0') }}" step="0.01" min="0"
                        class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Not</label>
                    <textarea name="notes" rows="3" maxlength="1000" class="w-full min-h-[4rem] rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm resize-y">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- Sağ: menü / ürünler --}}
            <div class="flex flex-col min-h-0 min-w-0 border border-slate-200 rounded-xl bg-slate-50/50">
                <div class="px-3 pt-3 pb-2 border-b border-slate-200 bg-white rounded-t-xl">
                    <h3 class="text-sm font-semibold text-slate-800">Menü — ürünler</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Boş satırlar yok sayılır.</p>
                </div>
                <div class="overflow-y-auto flex-1 max-h-[min(52vh,440px)] px-2 py-2">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-slate-100 z-10"><tr class="text-left text-slate-600"><th class="py-2 px-2 rounded-tl">Ürün</th><th class="py-2 px-2 w-20">Adet</th></tr></thead>
                        <tbody>
                            @for($i = 0; $i < 8; $i++)
                                <tr class="border-b border-slate-100 last:border-0">
                                    <td class="py-1.5 px-2">
                                        <select name="lines[{{ $i }}][product_id]" class="border border-slate-200 rounded-md px-2 py-1.5 w-full text-sm bg-white">
                                            <option value="">—</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}" @selected(old('lines.'.$i.'.product_id') == $p->id)>{{ $p->name }} ({{ number_format($p->effectiveUnitPrice(), 2) }} ₺)</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-1.5 px-2">
                                        <input type="number" name="lines[{{ $i }}][quantity]" min="1" value="{{ old('lines.'.$i.'.quantity', 1) }}"
                                            class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm bg-white">
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                @error('lines')
                    <p class="px-3 pb-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex flex-wrap gap-2 justify-end px-6 py-3 border-t border-slate-100 bg-slate-50/80 shrink-0">
            <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50" data-close-new-order>İptal</button>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">Siparişi oluştur</button>
        </div>
    </form>
</dialog>
