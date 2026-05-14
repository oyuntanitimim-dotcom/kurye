@php
    $slides = $data['slides'] ?? [];
    if (! is_array($slides)) {
        $slides = [];
    }
    $slides = array_values(array_filter($slides, static fn ($s): bool => is_array($s)));
    if ($slides === []) {
        $slides = [['headline' => '', 'subheadline' => '']];
    }
    $sliderId = 'hs-'.str_replace('-', '', (string) \Illuminate\Support\Str::uuid());

    $side = isset($data['side_banner']) && is_array($data['side_banner']) ? $data['side_banner'] : [];
    $showSide = ($side['title'] ?? '') !== ''
        || ($side['body'] ?? '') !== ''
        || (is_string($side['image'] ?? null) && ($side['image'] ?? '') !== '');
    $sideImageUrl = null;
    if ($showSide && is_string($side['image'] ?? null) && ($side['image'] ?? '') !== '') {
        $sideImageUrl = \Illuminate\Support\Str::startsWith($side['image'], ['http://', 'https://'])
            ? $side['image']
            : asset('storage/'.$side['image']);
    }
@endphp
<style>
@keyframes marketing-hero-float {
    0%, 100% { transform: translateY(0) rotateX(0deg); }
    50% { transform: translateY(-6px) rotateX(1.5deg); }
}
@keyframes marketing-hero-shimmer {
    0% { background-position: 200% 50%; }
    100% { background-position: -200% 50%; }
}
.marketing-hero-mock {
    animation: marketing-hero-float 6s ease-in-out infinite;
    transform-style: preserve-3d;
}
.marketing-hero-shimmer {
    background-size: 200% auto;
    animation: marketing-hero-shimmer 8s linear infinite;
}
@media (prefers-reduced-motion: reduce) {
    .marketing-hero-mock, .marketing-hero-shimmer { animation: none; }
}
.hero-slider-panels > .hero-slider-panel {
    grid-area: 1 / 1 / -1 / -1;
    align-self: stretch;
}
@media (prefers-reduced-motion: reduce) {
    .hero-slider-panel { transition: none !important; }
}
</style>
<div class="marketing-hero-block {{ $showSide ? 'mx-auto max-w-7xl px-4 pt-2 sm:px-6 lg:px-8' : '' }}">
@if($showSide)
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-stretch lg:gap-5 xl:grid-cols-[minmax(0,1fr)_26rem]">
        <div class="relative min-h-0 min-w-0">
@endif
<section class="@if($showSide) flex h-full min-h-0 flex-col @endif relative overflow-hidden border-b border-red-950/50 bg-gradient-to-br from-red-950 via-red-900 to-[#120303] text-white {{ $showSide ? 'rounded-2xl border border-red-900/70 shadow-2xl shadow-black/35 ring-1 ring-red-800/25' : '' }}" aria-roledescription="carousel" data-hero-slider="{{ $sliderId }}">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_85%_55%_at_75%_0%,rgba(248,113,113,0.14),transparent_52%)]" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_55%_45%_at_15%_100%,rgba(127,29,29,0.45),transparent_50%)]" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 opacity-[0.18] [background-image:linear-gradient(rgba(220,38,38,.07)_1px,transparent_1px),linear-gradient(90deg,rgba(220,38,38,.07)_1px,transparent_1px)] [background-size:56px_56px]" aria-hidden="true"></div>

    <div class="hero-slider-panels isolate grid grid-cols-1 grid-rows-1 @if($showSide) min-h-0 flex-1 @endif">
    @foreach ($slides as $idx => $slide)
        @php
            $banner = $slide['banner_image'] ?? null;
            $bannerUrl = null;
            if (is_string($banner) && $banner !== '') {
                $bannerUrl = \Illuminate\Support\Str::startsWith($banner, ['http://', 'https://']) ? $banner : asset('storage/'.$banner);
            }
            $panelBase = 'hero-slider-panel w-full min-w-0 self-stretch transition-opacity duration-200 ease-out motion-reduce:transition-none ';
            $panelBase .= $idx === 0
                ? 'visible z-10 opacity-100'
                : 'invisible z-0 opacity-0 pointer-events-none';
            $accent = trim((string) ($slide['headline_accent'] ?? ''));
            $headline = (string) ($slide['headline'] ?? '');
            $pillars = $slide['pillars'] ?? [];
            if (! is_array($pillars)) {
                $pillars = [];
            }
        @endphp
        <article
            id="{{ $sliderId }}-slide-{{ $idx }}"
            class="{{ $panelBase }} @if($showSide) flex flex-col @endif"
            data-hero-slider-panel="{{ $sliderId }}"
            data-slide-index="{{ $idx }}"
            role="group"
            aria-roledescription="slide"
            aria-label="Slayt {{ $idx + 1 }} / {{ count($slides) }}"
            @if($idx !== 0) aria-hidden="true" @endif
        >
            <div class="mx-auto grid min-h-[118px] w-full max-w-7xl flex-1 grid-cols-1 gap-0 px-4 py-5 sm:px-6 lg:min-h-[176px] lg:grid-cols-2 lg:items-center lg:gap-12 lg:px-8 lg:py-7">
                <div class="flex max-w-xl flex-col justify-center lg:border-r lg:border-red-800/35 lg:pr-8">
                    @if(! empty($slide['badge']))
                        <span class="inline-flex w-fit items-center rounded-full border border-white/15 bg-white/5 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.2em] text-red-300/90">{{ $slide['badge'] }}</span>
                    @endif
                    @if($idx === 0)
                        @if(($heroHeadingTag ?? 'h1') === 'h1')
                            <h1 class="mt-3 text-2xl font-extrabold leading-[1.15] tracking-tight text-white sm:text-3xl lg:text-[2.25rem]">
                                @if($accent !== '' && str_contains($headline, $accent))
                                    @php $parts = explode($accent, $headline, 2); @endphp
                                    {{ $parts[0] }}<span class="text-red-300">{{ $accent }}</span>{{ $parts[1] ?? '' }}
                                @else
                                    {{ $headline }}
                                @endif
                            </h1>
                        @else
                            <h2 class="mt-3 text-2xl font-extrabold leading-[1.15] tracking-tight text-white sm:text-3xl lg:text-[2.25rem]">
                                @if($accent !== '' && str_contains($headline, $accent))
                                    @php $parts = explode($accent, $headline, 2); @endphp
                                    {{ $parts[0] }}<span class="text-red-300">{{ $accent }}</span>{{ $parts[1] ?? '' }}
                                @else
                                    {{ $headline }}
                                @endif
                            </h2>
                        @endif
                    @else
                        <p class="mt-3 text-2xl font-extrabold leading-[1.15] tracking-tight text-white sm:text-3xl lg:text-[2.25rem]" role="heading" aria-level="2">
                            @if($accent !== '' && str_contains($headline, $accent))
                                @php $parts = explode($accent, $headline, 2); @endphp
                                {{ $parts[0] }}<span class="text-red-300">{{ $accent }}</span>{{ $parts[1] ?? '' }}
                            @else
                                {{ $headline }}
                            @endif
                        </p>
                    @endif
                    @if(! empty($slide['subheadline']))
                        <p class="mt-3 max-w-lg text-sm leading-relaxed text-red-100/75 sm:text-base">{{ $slide['subheadline'] }}</p>
                    @endif
                    <div class="mt-4 flex flex-col gap-2.5 sm:flex-row sm:flex-wrap sm:items-center">
                        @if(! empty($slide['cta_label']) && ! empty($slide['cta_url']))
                            <a href="{{ $slide['cta_url'] }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-500 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-black/30 ring-1 ring-red-300/35 transition hover:bg-red-400">
                                {{ $slide['cta_label'] }}
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                            </a>
                        @endif
                        @if(! empty($slide['cta_secondary_label']) && ! empty($slide['cta_secondary_url']))
                            <a href="{{ $slide['cta_secondary_url'] }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/5 px-6 py-3 text-sm font-semibold text-white backdrop-blur transition hover:border-white/35 hover:bg-white/10">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/10" aria-hidden="true">
                                    <svg class="ml-0.5 h-3.5 w-3.5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </span>
                                {{ $slide['cta_secondary_label'] }}
                            </a>
                        @endif
                    </div>
                    @if($pillars !== [])
                        <ul class="mt-4 flex flex-col gap-4 border-t border-white/10 pt-4 sm:flex-row sm:flex-wrap sm:gap-6">
                            @foreach (array_slice($pillars, 0, 3) as $p)
                                @if(is_array($p))
                                    <li class="flex items-start gap-2.5 text-sm text-red-100/85">
                                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-red-400" aria-hidden="true">
                                            @if(($p['icon'] ?? '') === 'map')
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                                            @elseif(($p['icon'] ?? '') === 'chart')
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                            @else
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                            @endif
                                        </span>
                                        <span class="leading-snug">{{ $p['label'] ?? '' }}</span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div class="relative mt-4 flex min-h-[92px] flex-col lg:mt-0 lg:min-h-[134px]">
                    @if($bannerUrl)
                        <div class="relative flex h-full min-h-[92px] flex-1 lg:min-h-[134px]">
                            <img src="{{ $bannerUrl }}" alt="{{ \Illuminate\Support\Str::limit(trim(strip_tags((string) ($slide['headline'] ?? 'Ürün görseli'))), 120) }}" class="h-full w-full rounded-xl object-cover object-left-top shadow-2xl ring-1 ring-white/10" loading="{{ $idx === 0 ? 'eager' : 'lazy' }}" width="960" height="640">
                            <div class="pointer-events-none absolute inset-0 rounded-xl bg-gradient-to-t from-red-950/80 via-transparent to-transparent" aria-hidden="true"></div>
                        </div>
                    @else
                        <div class="marketing-hero-mock flex flex-1 items-center justify-center p-1 sm:p-2">
                            @include('marketing.partials.hero-split-mockup')
                        </div>
                    @endif
                </div>
            </div>
        </article>
    @endforeach
    </div>

    @if(count($slides) > 1)
        <div class="absolute bottom-4 left-0 right-0 z-20 flex justify-center gap-2 px-4 pb-2" role="tablist" aria-label="Slayt seçici">
            @foreach ($slides as $idx => $_)
                <button
                    type="button"
                    class="h-2 min-w-2 rounded-full bg-red-950/60 transition-all data-[active=true]:w-8 data-[active=true]:bg-red-300"
                    data-hero-slider-dot="{{ $sliderId }}"
                    data-index="{{ $idx }}"
                    data-active="{{ $idx === 0 ? 'true' : 'false' }}"
                    aria-controls="{{ $sliderId }}-slide-{{ $idx }}"
                    aria-selected="{{ $idx === 0 ? 'true' : 'false' }}"
                    aria-label="Slayt {{ $idx + 1 }}"
                ></button>
            @endforeach
        </div>
        <button type="button" class="absolute left-3 top-1/2 z-20 hidden -translate-y-1/2 rounded-full border border-red-800/60 bg-red-950/90 p-2.5 text-red-100 backdrop-blur transition hover:border-red-600/50 hover:bg-red-900 sm:left-4 sm:block" data-hero-slider-prev="{{ $sliderId }}" aria-label="Önceki slayt">‹</button>
        <button type="button" class="absolute right-3 top-1/2 z-20 hidden -translate-y-1/2 rounded-full border border-red-800/60 bg-red-950/90 p-2.5 text-red-100 backdrop-blur transition hover:border-red-600/50 hover:bg-red-900 sm:right-4 sm:block" data-hero-slider-next="{{ $sliderId }}" aria-label="Sonraki slayt">›</button>
    @endif
</section>
@if($showSide)
        </div>
        @include('marketing.partials.hero-side-banner', ['side' => $side, 'sideImageUrl' => $sideImageUrl])
    </div>
@endif
</div>

@once
@push('scripts')
<script>
(function () {
    function bindSlider(root) {
        var rootId = root.getAttribute('data-hero-slider');
        if (!rootId) return;
        var panels = Array.prototype.slice.call(root.querySelectorAll('[data-hero-slider-panel="' + rootId + '"]'));
        var dots = Array.prototype.slice.call(root.querySelectorAll('[data-hero-slider-dot="' + rootId + '"]'));
        var prev = root.querySelector('[data-hero-slider-prev="' + rootId + '"]');
        var next = root.querySelector('[data-hero-slider-next="' + rootId + '"]');
        if (panels.length < 2) return;
        var i = 0;
        function show(n) {
            i = (n + panels.length) % panels.length;
            panels.forEach(function (el, idx) {
                var on = idx === i;
                el.classList.toggle('invisible', !on);
                el.classList.toggle('opacity-0', !on);
                el.classList.toggle('pointer-events-none', !on);
                el.classList.toggle('opacity-100', on);
                el.classList.toggle('visible', on);
                el.classList.toggle('z-10', on);
                el.classList.toggle('z-0', !on);
                el.setAttribute('aria-hidden', on ? 'false' : 'true');
            });
            dots.forEach(function (d, idx) {
                d.setAttribute('data-active', idx === i ? 'true' : 'false');
                d.setAttribute('aria-selected', idx === i ? 'true' : 'false');
            });
        }
        if (prev) prev.addEventListener('click', function () { show(i - 1); });
        if (next) next.addEventListener('click', function () { show(i + 1); });
        dots.forEach(function (d, idx) {
            d.addEventListener('click', function () { show(idx); });
        });
        setInterval(function () { show(i + 1); }, 9000);
    }
    document.querySelectorAll('[data-hero-slider]').forEach(bindSlider);
})();
</script>
@endpush
@endonce
