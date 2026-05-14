@php
    /** @var array<string, mixed> $side */
    /** @var string|null $sideImageUrl */
    $bannerMap = asset('images/marketing/hero-map.png');
@endphp
<aside class="relative flex min-h-[97px] flex-col overflow-hidden rounded-3xl border border-red-900/55 bg-gradient-to-br from-red-950 via-red-900 to-[#120303] text-white shadow-xl shadow-black/30 ring-1 ring-red-800/25 sm:min-h-[101px] lg:min-h-full" aria-label="Ek tanıtım">
    @if(! empty($sideImageUrl))
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <img src="{{ $sideImageUrl }}" alt="{{ \Illuminate\Support\Str::limit(trim(strip_tags((string) ($side['title'] ?? 'Tanıtım'))), 80) }}" class="h-full w-full object-cover opacity-35">
        </div>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-red-950 via-red-950/80 to-red-950/35" aria-hidden="true"></div>
    @endif
    <div class="relative z-10 flex flex-1 flex-col items-center justify-start gap-2.5 p-3 text-center sm:gap-3 sm:p-4">
        @if(! empty($side['badge']))
            <span class="inline-flex w-fit rounded-full border border-white/25 bg-white/10 px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-[0.2em] text-red-100">{{ $side['badge'] }}</span>
        @endif

        @if(! empty($side['title']))
            <p class="max-w-[20rem] text-base font-extrabold leading-snug tracking-tight sm:max-w-[22rem] sm:text-lg">{{ $side['title'] }}</p>
        @endif

        @if(! empty($side['body']))
            <p class="max-w-[20rem] text-xs leading-relaxed text-red-50/90 sm:max-w-[22rem] sm:text-xs">{{ $side['body'] }}</p>
        @endif

        <div class="w-full max-w-[18rem] overflow-hidden rounded-2xl border border-white/10 bg-red-950/25 shadow-lg shadow-black/20 sm:max-w-[20rem]">
            <img src="{{ $bannerMap }}" alt="Canlı harita ekranı" class="h-auto w-full object-cover" loading="lazy" width="880" height="720" />
        </div>

        @if(! empty($side['cta_label']) && ! empty($side['cta_url']))
            <a href="{{ $side['cta_url'] }}" class="mt-1 inline-flex items-center justify-center rounded-xl bg-white px-5 py-2.5 text-xs font-extrabold text-red-700 shadow-md ring-1 ring-red-100 transition hover:bg-red-50 sm:text-sm">{{ $side['cta_label'] }}</a>
        @endif
    </div>
</aside>
