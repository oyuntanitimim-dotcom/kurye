<section id="panel-onizleme" class="scroll-mt-28">
    <div class="mx-auto max-w-2xl text-center">
        @if(! empty($data['title']))
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-red-600/90">Yönetim</p>
            <h2 class="mt-2 text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">{{ $data['title'] }}</h2>
        @endif
        @if(! empty($data['subtitle']))
            <p class="mt-3 text-zinc-600">{{ $data['subtitle'] }}</p>
        @endif
    </div>

    <div class="mx-auto mt-12 grid max-w-6xl gap-6 lg:grid-cols-3">
        @foreach($data['items'] ?? [] as $item)
            @php
                $hasImage = ! empty($item['image']);
                $src = $hasImage
                    ? (\Illuminate\Support\Str::startsWith($item['image'], ['http://', 'https://']) ? $item['image'] : asset('storage/'.$item['image']))
                    : null;
            @endphp
            <figure class="flex h-full flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-[0_8px_30px_-8px_rgba(0,0,0,0.1)] ring-1 ring-zinc-950/[0.02]">
                <div class="border-b border-zinc-100 bg-zinc-50 px-4 py-3">
                    <p class="text-sm font-bold text-zinc-900">{{ $item['title'] ?? 'Panel' }}</p>
                    @if(! empty($item['caption']))
                        <p class="mt-0.5 text-xs text-zinc-500">{{ $item['caption'] }}</p>
                    @endif
                </div>
                <div class="flex min-h-[200px] flex-1 flex-col bg-white">
                    @if($hasImage && $src)
                        <img src="{{ $src }}" alt="{{ $item['title'] ?? '' }}" class="h-full min-h-[200px] w-full flex-1 object-cover object-top" loading="lazy" />
                    @else
                        @if(($loop->index % 3) === 0)
                            {{-- Sipariş tablosu --}}
                            <div class="flex flex-1 flex-col p-3 text-[10px] sm:text-xs">
                                <div class="grid grid-cols-5 gap-1 border-b border-zinc-100 pb-1.5 font-semibold uppercase tracking-wide text-zinc-400">
                                    <span>No</span><span>Restoran</span><span class="col-span-2">Müşteri</span><span>Durum</span>
                                </div>
                                <div class="mt-1 space-y-1">
                                    <div class="grid grid-cols-5 items-center gap-1 rounded bg-zinc-50 py-1.5 text-zinc-700">
                                        <span class="font-mono text-red-600">#901</span><span class="truncate">Lokanta</span><span class="col-span-2 truncate">Ayşe K.</span><span class="rounded bg-red-100 px-1 py-0.5 text-center text-[9px] font-semibold text-red-900">Hazır</span>
                                    </div>
                                    <div class="grid grid-cols-5 items-center gap-1 rounded bg-zinc-50 py-1.5 text-zinc-700">
                                        <span class="font-mono text-red-600">#902</span><span class="truncate">Burger</span><span class="col-span-2 truncate">Mehmet T.</span><span class="rounded bg-red-100 px-1 py-0.5 text-center text-[9px] font-semibold text-red-800">Yolda</span>
                                    </div>
                                    <div class="grid grid-cols-5 items-center gap-1 rounded bg-zinc-50 py-1.5 text-zinc-700">
                                        <span class="font-mono text-red-600">#903</span><span class="truncate">Pizza</span><span class="col-span-2 truncate">Zeynep A.</span><span class="rounded bg-zinc-200 px-1 py-0.5 text-center text-[9px] font-semibold text-zinc-800">Teslim</span>
                                    </div>
                                </div>
                            </div>
                        @elseif(($loop->index % 3) === 1)
                            {{-- Harita --}}
                            <div class="relative m-3 flex-1 overflow-hidden rounded-xl border border-zinc-100 bg-gradient-to-br from-zinc-100 to-zinc-200">
                                <div class="absolute inset-0 opacity-60 [background-image:radial-gradient(circle_at_30%_60%,rgba(220,38,38,0.25),transparent_50%),radial-gradient(circle_at_70%_40%,rgba(220,38,38,0.15),transparent_45%)]"></div>
                                <svg class="absolute inset-0 h-full w-full text-red-500/50" preserveAspectRatio="none" aria-hidden="true">
                                    <path d="M 20 100 Q 80 40 160 70 T 300 50" fill="none" stroke="currentColor" stroke-width="1.5" stroke-dasharray="3 2" />
                                </svg>
                                <div class="absolute bottom-3 left-3 right-3 rounded-lg border border-zinc-200 bg-white/95 p-2 shadow-md backdrop-blur">
                                    <p class="text-[9px] font-bold uppercase text-zinc-400">Kurye</p>
                                    <p class="text-[11px] font-semibold text-zinc-900">Canlı konum</p>
                                </div>
                            </div>
                        @else
                            {{-- Grafik --}}
                            <div class="flex flex-1 flex-col gap-3 p-4">
                                <div class="flex items-end justify-between gap-1 pt-4">
                                    @foreach ([40, 65, 45, 80, 55, 90, 70] as $h)
                                        <div class="flex-1 rounded-t bg-gradient-to-t from-red-600 to-red-400" style="height: {{ $h }}px"></div>
                                    @endforeach
                                </div>
                                <div class="mt-auto flex items-center justify-center gap-4 border-t border-zinc-100 pt-3">
                                    <div class="relative h-14 w-14 rounded-full border-4 border-red-100" style="background: conic-gradient(rgb(220 38 38) 0deg 220deg, rgb(228 228 231) 220deg 360deg);" aria-hidden="true"></div>
                                    <div class="text-[10px] text-zinc-500">
                                        <p class="font-semibold text-zinc-800">Dağılım</p>
                                        <p>Yolda · Teslim · İptal</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </figure>
        @endforeach
    </div>
</section>
