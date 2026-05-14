<section class="relative mt-20 overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-950 px-6 py-14 text-center text-white sm:px-12 sm:py-16">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(220,38,38,0.2),transparent_45%)]" aria-hidden="true"></div>
    <div class="relative mx-auto max-w-2xl">
        @if(! empty($data['title']))
            <h2 class="text-2xl font-bold sm:text-3xl">{{ $data['title'] }}</h2>
        @endif
        @if(! empty($data['body']))
            <p class="mx-auto mt-4 text-base leading-relaxed text-zinc-400 sm:text-lg">{{ $data['body'] }}</p>
        @endif
        <div class="mt-10 flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center">
            @if(! empty($data['cta_label']) && ! empty($data['cta_url']))
                <a href="{{ $data['cta_url'] }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-8 py-3.5 text-sm font-bold text-white shadow-lg shadow-black/30 ring-1 ring-red-500/40 transition hover:bg-red-500">
                    {{ $data['cta_label'] }}
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                </a>
            @endif
            @if(! empty($data['cta_secondary_label']) && ! empty($data['cta_secondary_url']))
                <a href="{{ $data['cta_secondary_url'] }}" class="inline-flex items-center justify-center rounded-xl border border-white/25 bg-transparent px-8 py-3.5 text-sm font-semibold text-white transition hover:border-white/40 hover:bg-white/5">
                    {{ $data['cta_secondary_label'] }}
                </a>
            @endif
        </div>
    </div>
</section>
