@php
    $highlights = $data['highlights'] ?? [];
    if (! is_array($highlights)) {
        $highlights = [];
    }
    $trustLine = $data['trust_line'] ?? null;
@endphp
<style>
@keyframes marketing-hero-float {
    0%, 100% { transform: translateY(0) rotateX(0deg); }
    50% { transform: translateY(-8px) rotateX(2deg); }
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
</style>
<section class="marketing-hero relative overflow-hidden rounded-3xl border border-red-800/40 bg-gradient-to-br from-red-950 via-red-800 to-red-950 text-white shadow-[0_32px_64px_-16px_rgba(127,29,29,0.45)] ring-1 ring-white/5">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_90%_60%_at_70%_-10%,rgba(254,226,226,0.2),transparent_55%)]" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_60%_50%_at_0%_100%,rgba(248,113,113,0.12),transparent_50%)]" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-20 top-1/2 h-[120%] w-[55%] -translate-y-1/2 rounded-full bg-red-600/15 blur-[100px]" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 opacity-[0.4] [background-image:linear-gradient(rgba(255,255,255,.05)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.05)_1px,transparent_1px)] [background-size:56px_56px]" aria-hidden="true"></div>

    <div class="relative z-10 grid items-center gap-12 px-6 py-14 sm:px-10 sm:py-16 lg:grid-cols-12 lg:gap-10 lg:py-20">
        <div class="lg:col-span-6">
            @if(! empty($data['badge']))
                <span class="inline-flex items-center gap-2 rounded-full border border-red-400/25 bg-red-500/10 px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-red-200/95 shadow-sm shadow-red-900/20">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-60"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-red-400"></span>
                    </span>
                    {{ $data['badge'] }}
                </span>
            @endif
            @if(($heroHeadingTag ?? 'h1') === 'h1')
                <h1 class="mt-5 text-[1.65rem] font-extrabold leading-[1.12] tracking-tight sm:text-4xl lg:text-[2.65rem] lg:leading-[1.08]">
                    <span class="text-white">{{ $data['headline'] ?? '' }}</span>
                </h1>
            @else
                <h2 class="mt-5 text-[1.65rem] font-extrabold leading-[1.12] tracking-tight sm:text-4xl lg:text-[2.65rem] lg:leading-[1.08]">
                    <span class="text-white">{{ $data['headline'] ?? '' }}</span>
                </h2>
            @endif
            @if(! empty($data['subheadline']))
                <p class="mt-5 max-w-xl text-base leading-relaxed text-red-50 sm:text-lg sm:leading-relaxed">{{ $data['subheadline'] }}</p>
            @endif
            @if(count($highlights) > 0)
                <ul class="mt-8 flex flex-wrap gap-2.5" aria-label="Öne çıkanlar">
                    @foreach(array_slice($highlights, 0, 5) as $h)
                        <li class="rounded-lg border border-white/10 bg-white/[0.06] px-3 py-1.5 text-xs font-semibold text-red-50 backdrop-blur-sm sm:text-sm">{{ $h }}</li>
                    @endforeach
                </ul>
            @endif
            <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                @if(! empty($data['cta_label']) && ! empty($data['cta_url']))
                    <a href="{{ $data['cta_url'] }}" class="group inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-8 py-4 text-base font-bold text-red-700 shadow-xl shadow-red-950/20 ring-1 ring-red-100 transition hover:bg-red-50">
                        {{ $data['cta_label'] }}
                        <span class="transition group-hover:translate-x-0.5" aria-hidden="true">→</span>
                    </a>
                @endif
                @if(! empty($data['cta_secondary_label']) && ! empty($data['cta_secondary_url']))
                    <a href="{{ $data['cta_secondary_url'] }}" class="inline-flex items-center justify-center rounded-2xl border border-white/20 bg-white/5 px-7 py-4 text-base font-semibold text-white backdrop-blur transition hover:border-white/35 hover:bg-white/10">
                        {{ $data['cta_secondary_label'] }}
                    </a>
                @endif
            </div>
            @if(! empty($trustLine))
                <p class="mt-8 text-sm font-medium text-red-200/80">{{ $trustLine }}</p>
            @endif
        </div>

        <div class="relative lg:col-span-6" aria-hidden="true">
            <div class="marketing-hero-mock relative mx-auto w-full max-w-md perspective-[1200px] lg:max-w-none">
                <div class="absolute -inset-4 rounded-[2rem] bg-gradient-to-tr from-red-500/20 via-transparent to-white/15 opacity-80 blur-2xl"></div>
                <div class="relative rotate-0 overflow-hidden rounded-2xl border border-white/10 bg-red-950/90 shadow-2xl ring-1 ring-white/10 backdrop-blur-md lg:-rotate-1 lg:translate-x-2">
                    <div class="flex h-9 items-center gap-1.5 border-b border-white/5 bg-red-950/80 px-3">
                        <span class="h-2.5 w-2.5 rounded-full bg-red-400/90"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-red-400/90"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-white/50"></span>
                        <span class="ml-3 flex-1 truncate rounded-md bg-red-900/80 py-1 text-center text-[10px] font-medium text-red-200/80">app.{{ \Illuminate\Support\Str::slug(config('app.name')) }}.com / operasyon</span>
                    </div>
                    <div class="flex min-h-[280px] sm:min-h-[320px]">
                        <div class="hidden w-14 shrink-0 border-r border-white/5 bg-red-950/50 py-3 sm:block">
                            <div class="mx-auto mb-2 h-8 w-8 rounded-lg bg-red-600/30"></div>
                            <div class="mx-auto mt-3 space-y-2 px-2">
                                @foreach(['','','',''] as $_)
                                    <div class="h-1.5 rounded bg-white/10"></div>
                                @endforeach
                            </div>
                        </div>
                        <div class="min-w-0 flex-1 p-3 sm:p-4">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-red-300/80">Canlı tahta</span>
                                <span class="rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-semibold text-red-100">● 12 aktif</span>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <div class="rounded-xl border border-white/10 bg-gradient-to-br from-red-900/80 to-red-950/80 p-3">
                                    <p class="text-[10px] font-medium uppercase text-red-300/70">Bugün</p>
                                    <p class="marketing-hero-shimmer mt-1 bg-gradient-to-r from-white via-red-100 to-white bg-clip-text text-2xl font-black text-transparent">847</p>
                                    <p class="text-[10px] text-red-300/70">teslimat</p>
                                </div>
                                <div class="rounded-xl border border-white/10 bg-red-900/50 p-3">
                                    <p class="text-[10px] font-medium uppercase text-red-300/70">Bekleyen</p>
                                    <div class="mt-2 space-y-1.5">
                                        <div class="flex items-center gap-2 rounded-lg bg-red-950/80 px-2 py-1.5">
                                            <span class="h-6 w-6 shrink-0 rounded bg-red-500/25 text-center text-[10px] leading-6 text-red-50">#</span>
                                            <span class="truncate text-[10px] text-red-100/90">Atama bekliyor</span>
                                        </div>
                                        <div class="flex items-center gap-2 rounded-lg bg-red-950/80 px-2 py-1.5">
                                            <span class="h-6 w-6 shrink-0 rounded bg-white/15 text-center text-[10px] leading-6 text-red-50">▶</span>
                                            <span class="truncate text-[10px] text-red-100/90">Yolda</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 rounded-xl border border-white/10 bg-red-950/60 p-2.5">
                                <div class="mb-2 flex items-center justify-between text-[10px] text-red-300/80">
                                    <span>Harita</span>
                                    <span class="text-white/80">GPS açık</span>
                                </div>
                                <div class="relative h-20 overflow-hidden rounded-lg bg-gradient-to-br from-red-900 to-red-950">
                                    <div class="absolute inset-0 opacity-50 [background-image:radial-gradient(circle_at_30%_70%,rgba(254,226,226,0.35),transparent_40%),radial-gradient(circle_at_70%_30%,rgba(248,113,113,0.25),transparent_42%)]"></div>
                                    <div class="absolute left-[18%] top-[55%] h-3 w-3 rounded-full border-2 border-red-400 bg-red-500 shadow-lg shadow-red-500/50"></div>
                                    <div class="absolute left-[52%] top-[32%] h-3 w-3 rounded-full border-2 border-white/50 bg-red-400 shadow-lg shadow-red-500/40"></div>
                                    <div class="absolute left-[72%] top-[62%] h-3 w-3 rounded-full border-2 border-white/40 bg-white/90"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
