@extends('marketing.layout')

@push('head')
    @if(!empty($metaDescription))
        <meta name="description" content="{{ $metaDescription }}">
        <meta property="og:description" content="{{ $metaDescription }}">
    @endif
    <meta property="og:title" content="{{ $title ?? config('marketing.brand_name') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="tr_TR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? config('marketing.brand_name') }}">
    @if(!empty($metaDescription))
        <meta name="twitter:description" content="{{ $metaDescription }}">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
@endpush

@section('marketing_content')
    @php
        $displayBlocks = $blocks;
        if (empty($displayBlocks)) {
            $displayBlocks = \App\Services\Marketing\MarketingBootstrap::defaultBlocks();
        }
        $hasHeroSlider = collect($displayBlocks)->contains(
            static fn ($b): bool => is_array($b) && ($b['type'] ?? '') === 'hero_slider',
        );
        if ($hasHeroSlider) {
            $displayBlocks = array_values(array_filter(
                $displayBlocks,
                static fn ($b): bool => ! is_array($b) || ($b['type'] ?? '') !== 'hero',
            ));
        }

        // Karşılama ekranı yerleşimi (sert sıralama):
        // hero_slider zaten üstte render ediliyor. Burada amaç, stats altında şu sırayı garanti etmek:
        // stats -> integrations (Popüler platformlarla uyum) -> feature_strip (3 kart).
        $pickFirst = static function (array &$blocks, string $type): ?array {
            foreach ($blocks as $i => $b) {
                if (is_array($b) && ($b['type'] ?? '') === $type) {
                    array_splice($blocks, $i, 1);
                    return $b;
                }
            }
            return null;
        };

        $rest = $displayBlocks;
        $ordered = [];
        foreach (['stats', 'integrations', 'feature_strip'] as $t) {
            $picked = $pickFirst($rest, $t);
            if ($picked !== null) {
                $ordered[] = $picked;
            }
        }
        // stats/integrations/feature_strip dışındaki her şeyi mevcut sırayla ekle
        $displayBlocks = array_values(array_merge($ordered, $rest));
    @endphp

    @foreach ($displayBlocks as $block)
        @php
            $type = $block['type'] ?? 'rich_text';
            $data = $block['data'] ?? [];
            $hasSlides = ! empty($slides) && $slides->isNotEmpty();
            $heroHeadingTag = in_array($type, ['hero', 'hero_slider'], true) ? ($hasSlides ? 'h2' : 'h1') : null;
        @endphp
        @if ($type === 'hero_slider')
            {{-- Tam genişlik yalnızca kırmızı tonlar (beyaz karışımı yok) --}}
            <div class="marketing-hero-stage relative z-0 left-1/2 right-1/2 -translate-x-1/2 w-screen max-w-[100vw] overflow-x-clip bg-gradient-to-b from-red-800 via-red-700 to-red-900 pt-2 sm:pt-3 lg:pt-4 pb-10 sm:pb-12 lg:pb-14">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_100%_50%_at_50%_0%,rgba(220,38,38,0.22),transparent_55%)]" aria-hidden="true"></div>
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(69,10,10,0.22)_1px,transparent_1px)] bg-[length:20px_20px]" aria-hidden="true"></div>
                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-red-950/70 to-transparent" aria-hidden="true"></div>
                <div class="relative z-[1]">
                    <div class="marketing-hero-bleed relative mx-auto max-w-[100vw] overflow-hidden">
                        @include('marketing.blocks.hero_slider', ['data' => $data, 'heroHeadingTag' => $heroHeadingTag])
                    </div>
                    <div class="marketing-hero-postband border-t border-red-950/40 bg-gradient-to-r from-red-950 via-red-900 to-red-950 py-1.5 shadow-inner shadow-black/20" role="presentation" aria-hidden="true">
                        <div class="mx-auto flex h-1.5 max-w-7xl items-center justify-center gap-2 px-4 sm:gap-3">
                            <span class="h-1 w-8 rounded-full bg-red-500/35 sm:w-12"></span>
                            <span class="h-1 w-1.5 rounded-full bg-red-400/50"></span>
                            <span class="h-1 w-8 rounded-full bg-red-500/35 sm:w-12"></span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <div id="blog" class="sr-only" tabindex="-1">Blog</div>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pb-24 pt-4 sm:space-y-8 sm:px-6 sm:pt-6 lg:px-8">
        @foreach ($displayBlocks as $block)
            @php
                $type = $block['type'] ?? 'rich_text';
                $data = $block['data'] ?? [];
                $hasSlides = ! empty($slides) && $slides->isNotEmpty();
                $heroHeadingTag = in_array($type, ['hero', 'hero_slider'], true) ? ($hasSlides ? 'h2' : 'h1') : null;
            @endphp
            @if ($type !== 'hero_slider')
                @includeIf('marketing.blocks.'.$type, ['data' => $data, 'heroHeadingTag' => $heroHeadingTag])
            @endif
        @endforeach
    </div>
@endsection
