<section id="fiyatlandirma" class="scroll-mt-28">
    <div class="mx-auto max-w-2xl text-center">
        @if(! empty($data['title']))
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">{{ $data['title'] }}</h2>
        @endif
        @if(! empty($data['subtitle']))
            <p class="mt-3 text-zinc-600">{{ $data['subtitle'] }}</p>
        @endif
    </div>

    <div class="mx-auto mt-12 grid max-w-5xl gap-6 lg:grid-cols-3">
        @foreach($data['plans'] ?? [] as $plan)
            @php
                $highlight = ! empty($plan['highlight']);
            @endphp
            <div class="relative flex flex-col overflow-hidden rounded-2xl border {{ $highlight ? 'border-red-200 shadow-xl shadow-red-900/10 ring-2 ring-red-500/20' : 'border-zinc-200 bg-white shadow-sm' }} {{ $highlight ? 'bg-white' : '' }}">
                @if($highlight)
                    <div class="bg-red-600 px-6 py-3 text-center">
                        <span class="text-xs font-bold uppercase tracking-widest text-white/95">En çok tercih edilen</span>
                    </div>
                @endif
                <div class="flex flex-1 flex-col p-6 pt-6">
                    <h3 class="text-lg font-bold text-zinc-900">{{ $plan['name'] ?? '' }}</h3>
                    <p class="mt-3 flex items-baseline gap-1">
                        <span class="text-3xl font-extrabold text-zinc-900">{{ $plan['price'] ?? '' }}</span>
                        @if(! empty($plan['period']))
                            <span class="text-sm text-zinc-500">/ {{ $plan['period'] }}</span>
                        @endif
                    </p>
                    @if(! empty($plan['description']))
                        <p class="mt-2 text-sm text-zinc-600">{{ $plan['description'] }}</p>
                    @endif
                    <ul class="mt-6 flex-1 space-y-2.5 text-sm text-zinc-700">
                        @foreach($plan['features'] ?? [] as $f)
                            <li class="flex gap-2">
                                <span class="mt-0.5 shrink-0 font-bold text-red-600" aria-hidden="true">✓</span>
                                <span>{{ is_array($f) ? ($f['text'] ?? '') : $f }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if(! empty($plan['cta_label']) && ! empty($plan['cta_url']))
                        <a href="{{ $plan['cta_url'] }}" class="mt-8 inline-flex justify-center rounded-xl {{ $highlight ? 'bg-red-600 text-white hover:bg-red-700' : 'border border-zinc-300 bg-white text-zinc-900 hover:border-red-300 hover:bg-red-50' }} px-4 py-2.5 text-center text-sm font-bold transition">{{ $plan['cta_label'] }}</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
