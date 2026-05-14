<section class="marketing-stats relative z-50 -mt-20 sm:-mt-24">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-visible rounded-2xl border border-white/70 bg-white/95 shadow-2xl shadow-black/10 ring-1 ring-black/5 backdrop-blur">
            <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-2xl" aria-hidden="true">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_85%_55%_at_50%_-25%,rgba(220,38,38,0.12),transparent_55%)]"></div>
            </div>

            @if(!empty($data['title']))
                <div class="absolute left-1/2 top-0 z-10 -translate-x-1/2 -translate-y-1/2">
                    <h2 class="inline-flex items-center justify-center gap-2 rounded-full bg-red-600 px-4 py-2 text-center text-[11px] font-extrabold uppercase tracking-[0.22em] text-white shadow-lg shadow-black/20 ring-1 ring-red-500/35 sm:px-5 sm:text-xs">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-sm bg-white/90" aria-hidden="true"></span>
                        <span>{{ $data['title'] }}</span>
                    </h2>
                </div>
            @endif

            <div class="relative grid grid-cols-2 gap-x-0 gap-y-0 px-2 pb-3 pt-6 sm:grid-cols-4 sm:px-4 sm:pb-4 sm:pt-7">
                @foreach($data['items'] ?? [] as $stat)
                    <div class="flex flex-col items-center justify-center gap-1 rounded-xl px-3 py-3 text-center sm:py-4">
                        <div class="text-xl font-extrabold tracking-tight text-zinc-900 sm:text-2xl">{{ $stat['value'] ?? '' }}</div>
                        <div class="text-[11px] font-semibold text-zinc-600 sm:text-xs">{{ $stat['label'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

