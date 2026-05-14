@php
    $compact = (bool) ($compact ?? false);
@endphp
<div class="space-y-8 text-sm">
    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-3xl">
        <div><dt class="text-slate-500">Kanal</dt><dd class="font-medium">@if($order->sourceEnum()){{ $order->sourceEnum()->label() }}@if($order->marketplace_provider) <span class="text-slate-500">({{ $order->marketplace_provider }})</span>@endif @else — @endif</dd></div>
        <div><dt class="text-slate-500">Durum</dt><dd class="font-medium">{{ \App\Enums\OrderStatus::tryFrom($order->status)?->label() ?? $order->status }}</dd></div>
        <div><dt class="text-slate-500">Tutar</dt><dd class="font-medium">{{ number_format((float) $order->total_price, 2) }} ₺</dd></div>
        <div><dt class="text-slate-500">Teslimat ücreti (müşteri)</dt><dd>{{ number_format((float) $order->delivery_fee, 2) }} ₺</dd></div>
        <div><dt class="text-slate-500">İndirim</dt><dd>{{ number_format((float) $order->discount_amount, 2) }} ₺</dd></div>
        <div>
            <dt class="text-slate-500">Ödeme</dt>
            <dd class="font-medium">{{ $order->paymentMethodLabel() }}</dd>
            <dd class="text-xs text-slate-500 mt-0.5">Kayıt: <code class="bg-slate-100 px-1 rounded">{{ $order->payment_method ?: '—' }}</code></dd>
        </div>
        @if(!empty($showFirm))
            <div><dt class="text-slate-500">Kurye şirketi</dt><dd>{{ $order->firm?->name ?? '—' }}</dd></div>
        @endif
        <div><dt class="text-slate-500">Restoran</dt><dd>{{ $order->restaurant?->name ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Müşteri</dt><dd>
            @if($order->customer)
                {{ $order->customer->name }} · {{ $order->customer->email }}
            @else
                {{ $order->customerDisplayName() }}
                @if($order->customer_phone)
                    <span class="text-slate-600"> · {{ $order->customer_phone }}</span>
                @endif
            @endif
        </dd></div>
        <div><dt class="text-slate-500">Kurye</dt><dd>{{ $order->courier?->name ?? '—' }}</dd></div>
        @if(!$compact && $order->tracking_token)
            <div class="sm:col-span-2">
                <dt class="text-slate-500">Müşteri takip</dt>
                <dd class="break-all text-xs">
                    <a href="{{ url('/takip/'.$order->tracking_token) }}" class="text-amber-700 hover:underline" target="_blank" rel="noopener">{{ url('/takip/'.$order->tracking_token) }}</a>
                </dd>
            </div>
        @endif
    </dl>

    @if($order->deliveryAddress)
        <div>
            <h3 class="text-base font-medium mb-2">Teslimat adresi</h3>
            <p class="text-slate-700">{{ $order->deliveryAddress->title }} — {{ $order->deliveryAddress->address }}</p>
        </div>
    @endif

    @if($order->notes)
        <div>
            <h3 class="text-base font-medium mb-2">Not</h3>
            <p class="text-slate-700">{{ $order->notes }}</p>
        </div>
    @endif

    <div>
        <h3 class="text-base font-medium mb-3">Kalemler</h3>
        <table class="w-full text-sm border border-slate-200 rounded-lg overflow-hidden">
            <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left py-2 px-3">Ürün</th><th class="text-right py-2 px-3">Birim</th><th class="text-right py-2 px-3">Adet</th><th class="text-right py-2 px-3">Satır</th></tr></thead>
            <tbody>
                @foreach($order->items as $line)
                    <tr class="border-t border-slate-100">
                        <td class="py-2 px-3">{{ $line->product_name }}</td>
                        <td class="py-2 px-3 text-right">{{ number_format((float) $line->price, 2) }} ₺</td>
                        <td class="py-2 px-3 text-right">{{ $line->quantity }}</td>
                        <td class="py-2 px-3 text-right font-medium">{{ number_format((float) $line->price * $line->quantity, 2) }} ₺</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(!$compact)
        <div>
            <h3 class="text-base font-medium mb-3">Durum geçmişi</h3>
            <ul class="space-y-2 max-w-xl">
                @foreach($order->statusHistories->sortBy('created_at') as $h)
                    @php
                        $meta = is_array($h->meta) ? $h->meta : [];
                        $msg = (string) ($meta['message'] ?? $meta['note'] ?? $meta['reason'] ?? '');
                        $msg = trim($msg);
                    @endphp
                    <li class="border-b border-slate-100 py-1">
                        <div class="flex justify-between gap-3">
                            <span>{{ \App\Enums\OrderStatus::tryFrom($h->status)?->label() ?? $h->status }}</span>
                            <span class="shrink-0 text-slate-500">{{ $h->created_at?->format('d.m.Y H:i') }}</span>
                        </div>
                        @if($msg !== '')
                            <div class="mt-1 text-xs text-slate-600">{{ $msg }}</div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!empty($showFirm) && isset($order->dispatchDecisions) && $order->dispatchDecisions->isNotEmpty())
        @php
            $decisions = $order->dispatchDecisions->sortByDesc('created_at')->take(3);
            $labelTrigger = fn (string $t): string => match ($t) {
                'auto_ready' => 'Otomatik (hazır oldu)',
                'restaurant_courier_request' => 'Restoran: kurye çağır',
                'manual_ui' => 'Manuel UI (otomatik ata)',
                'story_manual' => 'Senaryo testi',
                default => $t,
            };
        @endphp
        <div>
            <h3 class="text-base font-medium mb-3">Otomatik atama kararları</h3>
            <div class="space-y-3 max-w-3xl">
                @foreach($decisions as $d)
                    @php
                        $cj = is_array($d->candidates_json) ? $d->candidates_json : [];
                        $err = (string) ($cj['error'] ?? '');
                        $cands = [];
                        if (isset($cj['candidates']) && is_array($cj['candidates'])) {
                            $cands = $cj['candidates'];
                        } elseif (is_array($cj) && isset($cj[0])) {
                            $cands = $cj; // eski format fallback
                        }
                        $scored = array_values(array_filter($cands, fn ($r) => is_array($r) && empty($r['excluded']) && isset($r['score'])));
                        usort($scored, fn ($a, $b) => ($a['score'] <=> $b['score']));
                        $top = array_slice($scored, 0, 8);
                    @endphp
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="text-sm font-medium text-slate-900">
                                {{ $labelTrigger((string) $d->trigger) }}
                                <span class="text-slate-500 font-normal">· {{ $d->created_at?->format('d.m.Y H:i') }}</span>
                            </div>
                            <div class="text-xs text-slate-600">
                                @if($d->chosenCourier)
                                    Seçilen: <strong>{{ $d->chosenCourier->name }}</strong> (#{{ $d->chosenCourier->id }})
                                @else
                                    Seçilen: <strong>—</strong>
                                @endif
                                @if($d->createdBy)
                                    <span class="text-slate-400">·</span> {{ $d->createdBy->name }}
                                @endif
                            </div>
                        </div>

                        @if($err !== '')
                            <p class="mt-2 text-xs text-red-700">Hata: <strong>{{ $err }}</strong></p>
                        @endif

                        @if(!empty($top))
                            <div class="mt-3 overflow-x-auto">
                                <table class="w-full text-xs border border-slate-200 rounded-lg overflow-hidden">
                                    <thead class="bg-slate-50 text-slate-600">
                                        <tr>
                                            <th class="text-left py-2 px-3">Kurye</th>
                                            <th class="text-right py-2 px-3">Skor</th>
                                            <th class="text-right py-2 px-3">Mesafe (km)</th>
                                            <th class="text-right py-2 px-3">Aktif</th>
                                            <th class="text-right py-2 px-3">Konum yaşı (dk)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($top as $row)
                                            <tr class="border-t border-slate-100">
                                                <td class="py-2 px-3">
                                                    #{{ $row['courier_id'] ?? '—' }}
                                                    @if((int) ($row['courier_id'] ?? 0) === (int) ($d->chosen_courier_id ?? 0))
                                                        <span class="ml-1 rounded bg-emerald-100 px-2 py-0.5 text-emerald-800">seçildi</span>
                                                    @endif
                                                </td>
                                                <td class="py-2 px-3 text-right font-medium">{{ $row['score'] ?? '—' }}</td>
                                                <td class="py-2 px-3 text-right">{{ $row['distance_km'] ?? '—' }}</td>
                                                <td class="py-2 px-3 text-right">{{ $row['active_orders'] ?? '—' }}</td>
                                                <td class="py-2 px-3 text-right">{{ $row['location_age_minutes'] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="mt-2 text-xs text-slate-500">Skorlu aday listesi yok (konum eksikliği veya uygun kurye bulunamadı).</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
