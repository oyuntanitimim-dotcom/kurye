@php
    use App\Enums\CourierLedgerEntryKind;
    use App\Enums\CourierPayoutPaymentMethod;
    $embed = (bool) ($embed ?? false);
    $ordersAllTime = (bool) ($ordersAllTime ?? false);
@endphp
@extends($embed ? 'layouts.firm-embed' : 'layouts.firm')

@section('content')
<h1 class="text-2xl font-semibold mb-2">{{ $title }}</h1>
<p class="text-sm text-slate-600 mb-4">
    Belirlediğiniz teslim döneminde, henüz kapatılmamış sipariş hakedişlerini ve (isteğe bağlı) açık cari kalemleri toplu olarak kapatır. Ödeme yöntemi: nakit, havale, EFT, çek vb.
    <a href="{{ route('firm.finance.courier_payouts.index') }}" @if($embed) target="_parent" rel="noopener" @endif class="ml-1 text-slate-700 underline">Geçmiş kayıtlar</a>
    @if($embed)
        <a href="{{ route('firm.finance.courier_payouts.create', request()->except('embed')) }}" target="_blank" rel="noopener" class="ml-2 text-slate-500 underline">Tam sayfada aç</a>
    @endif
</p>

@if(session('status'))
    <p class="mb-3 text-sm text-emerald-800">{{ session('status') }}</p>
@endif
@if($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
        {{ $errors->first() }}
    </div>
@endif

<div class="rounded-xl border border-slate-200 bg-white p-4 mb-6 text-sm">
    <h2 class="text-sm font-semibold text-slate-900 mb-3">1) Dönem ve kurye</h2>
    <form method="get" action="{{ route('firm.finance.courier_payouts.create') }}" class="flex flex-wrap items-end gap-3">
        @if($embed)
            <input type="hidden" name="embed" value="1">
        @endif
        <div>
            <label class="block text-slate-600 mb-1">Kurye</label>
            <select name="courier_id" class="rounded border border-slate-300 px-3 py-2 min-w-[12rem]" required>
                <option value="">Seçin</option>
                @foreach($couriers as $c)
                    <option value="{{ $c->id }}" @selected((int)$courierId === (int)$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-slate-600 mb-1">Hazır aralık</label>
            <select name="preset" class="rounded border border-slate-300 px-3 py-2 min-w-[11rem]">
                <option value="all" @selected($preset === 'all')>Tümü (ödenmemiş tüm hakedişler)</option>
                <option value="today" @selected($preset === 'today')>Bugün</option>
                <option value="week" @selected($preset === 'week')>Bu hafta</option>
                <option value="month" @selected($preset === 'month')>Bu ay</option>
                <option value="custom" @selected($preset === 'custom')>Tarih seç</option>
            </select>
        </div>
        <div class="@if($preset !== 'custom') hidden @endif">
            <label class="block text-slate-600 mb-1">Başlangıç</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="rounded border border-slate-300 px-3 py-2">
        </div>
        <div class="@if($preset !== 'custom') hidden @endif">
            <label class="block text-slate-600 mb-1">Bitiş</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="rounded border border-slate-300 px-3 py-2">
        </div>
        <div class="pb-0.5">
            <label class="flex items-center gap-2 text-slate-700">
                <input type="checkbox" name="include_all_ledger" value="1" @checked($includeAllLedger)>
                Tüm açık cari kalemleri dâhil et
            </label>
        </div>
        <button type="submit" class="rounded-lg bg-slate-200 px-4 py-2 font-medium">Hesapla</button>
    </form>
    <p class="mt-2 text-xs text-slate-500">@if($ordersAllTime) <strong>Tümü</strong> seçili: teslim tarihine bakılmaz; henüz kapatılmamış tüm sipariş hakedişleri dâhildir. Cari tarafta, işaretli değilse sadece aşağıdaki dönem tarihlerine ({{ $dateFrom }} – {{ $dateTo }}) oturan açık kalemler. @else Dönem: kullanılan teslim anı (sipariş <code class="bg-slate-100 px-1 rounded">updated_at</code>). @endif Cari: avans (ön ödeme) ve giderler hakedişten düşer; primler eklenir. @if($financeOnlineOrdersOnly ?? false) (Finans: yalnız online ödemeli siparişler) @endif</p>
</div>

@if($preview !== null)
    <div class="rounded-xl border border-slate-200 bg-white p-4 mb-6 text-sm">
        <h2 class="text-sm font-semibold text-slate-900 mb-2">2) Cari hareket ekle (isteğe bağlı)</h2>
        <form method="post" action="{{ route('firm.finance.courier_ledger.store') }}" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            @csrf
            @if($embed)
                <input type="hidden" name="embed" value="1">
            @endif
            <input type="hidden" name="courier_id" value="{{ (int) $courierId }}">
            <input type="hidden" name="preset" value="{{ $preset }}">
            <input type="hidden" name="date_from" value="{{ $dateFrom }}">
            <input type="hidden" name="date_to" value="{{ $dateTo }}">
            <input type="hidden" name="include_all_ledger" value="{{ $includeAllLedger ? 1 : 0 }}">
            <div>
                <label class="block text-slate-600 mb-1">Tür</label>
                <select name="entry_kind" class="w-full rounded border border-slate-300 px-3 py-2">
                    @foreach(CourierLedgerEntryKind::cases() as $k)
                        <option value="{{ $k->value }}">{{ $k->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-slate-600 mb-1">Tutar (₺)</label>
                <input type="text" name="amount" class="w-full rounded border border-slate-300 px-3 py-2" inputmode="decimal" placeholder="0,00" required>
            </div>
            <div>
                <label class="block text-slate-600 mb-1">Kalem tarihi</label>
                <input type="date" name="entry_date" value="{{ $dateTo }}" class="w-full rounded border border-slate-300 px-3 py-2" required>
            </div>
            <div>
                <label class="block text-slate-600 mb-1">Yol (avans/çek)</label>
                <select name="method" class="w-full rounded border border-slate-300 px-3 py-2">
                    <option value="">—</option>
                    @foreach(CourierPayoutPaymentMethod::cases() as $m)
                        <option value="{{ $m->value }}">{{ $m->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-slate-600 mb-1">Açıklama / referans</label>
                <input type="text" name="description" class="w-full rounded border border-slate-300 px-3 py-2" placeholder="örn. Çek no, yakıt stopaj">
            </div>
            <div>
                <label class="block text-slate-600 mb-1">Referans (ı)</label>
                <input type="text" name="reference" class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-amber-900 font-medium">Cari ekle &amp; listeyi yenile</button>
            </div>
        </form>
    </div>

    <div class="grid gap-3 lg:grid-cols-3 mb-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3">
            <p class="text-xs uppercase text-slate-500">Sipariş hakedişi (ödenmemiş)</p>
            <p class="text-lg font-bold text-slate-900">{{ number_format($preview['orders_total'], 2) }} ₺</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3">
            <p class="text-xs uppercase text-slate-500">Cari: kesinti + avans / prim</p>
            <p class="text-lg font-bold text-slate-900">-{{ number_format($preview['ledger_deductions'], 2) }} / +{{ number_format($preview['ledger_credits'], 2) }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-3">
            <p class="text-xs uppercase text-emerald-800">Net ödenecek</p>
            <p class="text-lg font-bold text-emerald-950">{{ number_format($preview['net'], 2) }} ₺</p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2 mb-6">
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <h3 class="px-3 py-2 text-xs font-semibold uppercase text-slate-500 border-b">Siparişler (kapatılacak)</h3>
            <div class="max-h-64 overflow-y-auto text-xs">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-slate-500 border-b">
                            <th class="py-2 px-2">#</th>
                            <th class="py-2 px-2">Teslim</th>
                            <th class="py-2 px-2">Hakediş</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($preview['orders'] as $o)
                            <tr class="border-b border-slate-50">
                                <td class="py-1.5 px-2">{{ $o->id }}</td>
                                <td class="py-1.5 px-2 text-slate-600">{{ $o->updated_at->format('Y-m-d H:i') }}</td>
                                <td class="py-1.5 px-2 font-medium">{{ number_format((float)($o->courier_payout_amount ?? 0), 2) }} ₺</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-3 px-2 text-slate-500">Bu aralıkta ödenmemiş hakediş yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <h3 class="px-3 py-2 text-xs font-semibold uppercase text-slate-500 border-b">Açık cari kalemler (kapanacak)</h3>
            <div class="max-h-64 overflow-y-auto text-xs">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-slate-500 border-b">
                            <th class="py-2 px-2">Tarih</th>
                            <th class="py-2 px-2">Tür</th>
                            <th class="py-2 px-2">Tutar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($preview['ledgers'] as $le)
                            <tr class="border-b border-slate-50">
                                <td class="py-1.5 px-2 text-slate-600">{{ $le->entry_date->format('Y-m-d') }}</td>
                                <td class="py-1.5 px-2">{{ $le->kindEnum()?->label() ?? $le->entry_kind }}</td>
                                <td class="py-1.5 px-2 font-medium">
                                    @if($le->entry_kind === 'credit')+@endif{{ number_format((float) $le->amount, 2) }} ₺
                                    <form class="inline ml-1" method="post" action="{{ route('firm.finance.courier_ledger.destroy', $le) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline" title="Sil">×</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-3 px-2 text-slate-500">Açık kalem yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">3) Ödeme onayı</h2>
        <form method="post" action="{{ route('firm.finance.courier_payouts.store') }}">
            @csrf
            @if($embed)
                <input type="hidden" name="embed" value="1">
            @endif
            <input type="hidden" name="courier_id" value="{{ (int) $courierId }}">
            <input type="hidden" name="preset" value="{{ $preset }}">
            <input type="hidden" name="date_from" value="{{ $dateFrom }}">
            <input type="hidden" name="date_to" value="{{ $dateTo }}">
            <input type="hidden" name="include_all_ledger" value="{{ $includeAllLedger ? 1 : 0 }}">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="block text-slate-600 mb-1">Ödeme yöntemi *</label>
                    <select name="payment_method" class="w-full rounded border border-slate-300 px-3 py-2" required>
                        @foreach($paymentMethods as $m)
                            <option value="{{ $m->value }}">{{ $m->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-600 mb-1">Referans (çek no, dekont…)</label>
                    <input type="text" name="payment_reference" class="w-full rounded border border-slate-300 px-3 py-2" value="{{ old('payment_reference') }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-slate-600 mb-1">Not</label>
                    <input type="text" name="notes" class="w-full rounded border border-slate-300 px-3 py-2" value="{{ old('notes') }}">
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-500">İşlem kayıttır; “Geçerli” kayıt siparişleri ödenmiş sayar. Hata durumunda detay ekranından <strong>iptal</strong> edebilirsiniz.</p>
            <div class="mt-4">
                <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 font-medium text-white hover:bg-slate-800" @if($preview['orders']->isEmpty() && $preview['ledgers']->isEmpty()) disabled @endif>
                    Net {{ number_format($preview['net'], 2) }} ₺ &mdash; kapat
                </button>
            </div>
        </form>
    </div>
@endif
@endsection
