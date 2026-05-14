@extends('layouts.app')

@php
    $wordmark = trim((string) config('marketing.brand_name'));
    $wordParts = preg_split('/\s+/u', $wordmark, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $footerBrand = $site->name ?? config('marketing.brand_name');
    $homeUrl = route('marketing.home');
@endphp

@section('body')
{{-- overflow-x-hidden üst kapsayıcıda olursa position:sticky çalışmaz; yatay taşma main içinde clip --}}
<div class="min-h-screen flex min-w-0 flex-col bg-zinc-100 text-zinc-900 antialiased">
    <header class="sticky top-0 z-50 border-b border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-900/5 backdrop-blur-md supports-[backdrop-filter]:bg-white/90">
        <div class="mx-auto grid max-w-7xl grid-cols-[1fr_auto] items-center gap-3 px-4 py-3.5 sm:px-6 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] lg:gap-4 lg:px-8">
            <a href="{{ $homeUrl }}" class="group inline-flex items-center gap-2 justify-self-start">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-600 text-white shadow-sm ring-1 ring-red-700/20" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                    </svg>
                </span>
                <span class="text-lg font-extrabold tracking-tight text-zinc-900 sm:text-xl">
                    @if(count($wordParts) >= 2)
                        <span class="text-red-600">{{ $wordParts[0] }}</span><span class="text-zinc-900"> {{ implode(' ', array_slice($wordParts, 1)) }}</span>
                    @else
                        <span class="text-red-600">{{ $wordmark }}</span>
                    @endif
                </span>
            </a>

            <nav class="col-span-2 flex max-w-full flex-nowrap items-center justify-start gap-0.5 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] sm:justify-center lg:col-span-1 lg:justify-center [&::-webkit-scrollbar]:hidden" aria-label="Ana menü">
                @php $navCount = isset($menuItems) ? $menuItems->count() : 0; @endphp
                @forelse($menuItems ?? [] as $item)
                    <a href="{{ $item->url }}" @if($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="shrink-0 rounded-md px-2.5 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-red-700 sm:px-3 sm:text-sm">{{ $item->label }}</a>
                @empty
                    <a href="{{ $homeUrl }}" class="shrink-0 rounded-md px-2.5 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-red-700 sm:px-3 sm:text-sm">Anasayfa</a>
                    <a href="{{ $homeUrl }}#hakkimizda" class="shrink-0 rounded-md px-2.5 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-red-700 sm:px-3 sm:text-sm">Hakkımızda</a>
                    <a href="{{ $homeUrl }}#ozellikler" class="shrink-0 rounded-md px-2.5 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-red-700 sm:px-3 sm:text-sm">Özellikler</a>
                    <a href="{{ $homeUrl }}#fiyatlandirma" class="shrink-0 rounded-md px-2.5 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-red-700 sm:px-3 sm:text-sm">Fiyatlarımız</a>
                    <a href="{{ $homeUrl }}#referanslar" class="shrink-0 rounded-md px-2.5 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-red-700 sm:px-3 sm:text-sm">Referanslar</a>
                    <a href="{{ $homeUrl }}#basvuru" class="shrink-0 rounded-md px-2.5 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-red-700 sm:px-3 sm:text-sm">Başvuru</a>
                    <a href="{{ $homeUrl }}#sss" class="shrink-0 rounded-md px-2.5 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-red-700 sm:px-3 sm:text-sm">S.S.S.</a>
                    <a href="{{ $homeUrl }}#iletisim" class="shrink-0 rounded-md px-2.5 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-red-700 sm:px-3 sm:text-sm">İletişim</a>
                    <a href="{{ route('login') }}" class="shrink-0 rounded-md px-2.5 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-red-700 sm:px-3 sm:text-sm">Panel Girişi</a>
                @endforelse
            </nav>

            <div class="flex shrink-0 items-center justify-end gap-2 justify-self-end sm:gap-2.5">
                <a href="{{ route('login') }}" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-semibold text-zinc-800 shadow-sm transition hover:border-zinc-400 hover:bg-zinc-50 sm:px-4 sm:text-sm">Giriş Yap</a>
                <a href="{{ $homeUrl }}#iletisim" class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-sm ring-1 ring-red-700/30 transition hover:bg-red-700 sm:px-4 sm:text-sm">Ücretsiz Dene</a>
            </div>
        </div>
    </header>

    @if(!empty($slides) && $slides->isNotEmpty())
        <section class="relative border-b border-red-900/20 bg-gradient-to-br from-red-950 via-red-800 to-red-950 text-white" aria-label="Öne çıkanlar">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_100%_80%_at_50%_-40%,rgba(254,226,226,0.2),transparent)]" aria-hidden="true"></div>
            <div class="marketing-carousel relative mx-auto max-w-7xl overflow-hidden px-4 sm:px-6 lg:px-8" data-carousel>
                @foreach($slides as $idx => $slide)
                    <article class="carousel-slide relative py-16 sm:py-24 {{ $idx === 0 ? '' : 'hidden' }}" data-slide="{{ $idx }}" @if($slide->image_path) style="background-image: linear-gradient(105deg,rgba(9,9,11,.92) 0%,rgba(69,10,10,.85) 45%,rgba(9,9,11,.75) 100%), url('{{ \Illuminate\Support\Str::startsWith($slide->image_path, ['http://','https://']) ? $slide->image_path : asset('storage/'.$slide->image_path) }}'); background-size: cover; background-position: center;" @endif>
                        <div class="relative max-w-2xl">
                            <h1 class="text-3xl font-bold leading-tight tracking-tight sm:text-4xl lg:text-5xl">{{ $slide->title }}</h1>
                            @if($slide->subtitle)
                                <p class="mt-4 text-lg leading-relaxed text-red-50 sm:text-xl">{{ $slide->subtitle }}</p>
                            @endif
                            @if($slide->cta_label && $slide->cta_url)
                                <a href="{{ $slide->cta_url }}" class="mt-10 inline-flex rounded-xl bg-red-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-red-950/20 ring-1 ring-red-500/30 transition hover:bg-red-500">{{ $slide->cta_label }}</a>
                            @endif
                        </div>
                    </article>
                @endforeach
                @if($slides->count() > 1)
                    <div class="absolute bottom-5 left-0 right-0 flex justify-center gap-2 pb-2">
                        @foreach($slides as $idx => $_)
                            <button type="button" class="h-2 w-2 rounded-full bg-white/30 transition data-[active=true]:w-6 data-[active=true]:bg-white" data-dot="{{ $idx }}" aria-label="Slayt {{ $idx + 1 }}"></button>
                        @endforeach
                    </div>
                    <button type="button" class="absolute left-2 top-1/2 z-10 -translate-y-1/2 rounded-full border border-white/10 bg-white/10 p-2.5 text-white backdrop-blur transition hover:bg-white/20 sm:left-4" data-carousel-prev aria-label="Önceki">‹</button>
                    <button type="button" class="absolute right-2 top-1/2 z-10 -translate-y-1/2 rounded-full border border-white/10 bg-white/10 p-2.5 text-white backdrop-blur transition hover:bg-white/20 sm:right-4" data-carousel-next aria-label="Sonraki">›</button>
                @endif
            </div>
        </section>
    @endif

    <main class="flex-1 min-w-0 overflow-x-clip">
        @if($preview ?? false)
            <div class="border-b border-red-200 bg-red-50 px-4 py-2 text-center text-sm font-medium leading-tight text-red-900">Taslak önizleme — ziyaretçiler göremez.</div>
        @endif
        @if(session('status'))
            <div class="border-b border-red-200 bg-red-50 px-4 py-3 text-center text-sm font-medium text-red-900">{{ session('status') }}</div>
        @endif
        @yield('marketing_content')
    </main>

    <footer class="relative mt-auto border-t border-zinc-800 bg-zinc-950 text-zinc-300">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/15 to-transparent" aria-hidden="true"></div>
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:gap-12 lg:px-8">
            @forelse($footerColumns ?? [] as $column)
                <div>
                    @if($column->heading)
                        <h3 class="text-xs font-bold uppercase tracking-[0.18em] text-zinc-500">{{ $column->heading }}</h3>
                    @endif
                    <ul class="mt-4 space-y-2.5 text-sm leading-tight">
                        @foreach($column->links as $link)
                            <li>
                                <a href="{{ $link->url }}" @if($link->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="font-medium text-zinc-300 transition hover:text-white">{{ $link->label }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.18em] text-zinc-500">Ürün</h3>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a href="{{ $homeUrl }}#ozellikler" class="font-medium text-zinc-300 transition hover:text-white">Özellikler</a></li>
                        <li><a href="{{ $homeUrl }}#fiyatlandirma" class="font-medium text-zinc-300 transition hover:text-white">Fiyatlandırma</a></li>
                        <li><a href="{{ route('login') }}" class="font-medium text-zinc-300 transition hover:text-white">Panel girişi</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.18em] text-zinc-500">Şirket</h3>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a href="{{ $homeUrl }}#blog" class="font-medium text-zinc-300 transition hover:text-white">Blog</a></li>
                        <li><a href="{{ $homeUrl }}#iletisim" class="font-medium text-zinc-300 transition hover:text-white">İletişim</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.18em] text-zinc-500">Destek</h3>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a href="{{ $homeUrl }}#iletisim" class="font-medium text-zinc-300 transition hover:text-white">Yardım merkezi</a></li>
                        <li><a href="{{ $homeUrl }}#kvkk" class="font-medium text-zinc-300 transition hover:text-white">KVKK</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.18em] text-zinc-500">Sosyal</h3>
                    <p class="mt-4 text-sm leading-relaxed text-zinc-400">Bizi sosyal ağlardan takip edin.</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="#" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-700 bg-zinc-900 text-zinc-400 transition hover:border-red-600/50 hover:text-red-400" aria-label="Facebook"><span class="text-xs font-bold">f</span></a>
                        <a href="#" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-700 bg-zinc-900 text-zinc-400 transition hover:border-red-600/50 hover:text-red-400" aria-label="X"><span class="text-xs font-bold">𝕏</span></a>
                        <a href="#" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-700 bg-zinc-900 text-zinc-400 transition hover:border-red-600/50 hover:text-red-400" aria-label="LinkedIn"><span class="text-xs font-bold">in</span></a>
                        <a href="#" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-700 bg-zinc-900 text-zinc-400 transition hover:border-red-600/50 hover:text-red-400" aria-label="Instagram"><span class="text-xs font-bold">◎</span></a>
                    </div>
                </div>
            @endforelse
        </div>
        <div class="border-t border-zinc-800">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-5 sm:flex-row sm:px-6 lg:px-8">
                <p class="text-center text-xs text-zinc-500 sm:text-left">© {{ date('Y') }} {{ $footerBrand }}. Tüm hakları saklıdır.</p>
                <div class="flex gap-3">
                    <a href="#" class="text-zinc-500 transition hover:text-red-400" aria-label="Facebook">FB</a>
                    <a href="#" class="text-zinc-500 transition hover:text-red-400" aria-label="X">X</a>
                    <a href="#" class="text-zinc-500 transition hover:text-red-400" aria-label="LinkedIn">LI</a>
                    <a href="#" class="text-zinc-500 transition hover:text-red-400" aria-label="Instagram">IG</a>
                </div>
            </div>
        </div>
    </footer>
</div>
@push('scripts')
<script>
(function () {
    const root = document.querySelector('[data-carousel]');
    if (!root) return;
    const slides = Array.from(root.querySelectorAll('.carousel-slide'));
    const dots = Array.from(root.querySelectorAll('[data-dot]'));
    let i = 0;
    function show(n) {
        i = (n + slides.length) % slides.length;
        slides.forEach((el, idx) => el.classList.toggle('hidden', idx !== i));
        dots.forEach((d, idx) => d.setAttribute('data-active', idx === i ? 'true' : 'false'));
    }
    root.querySelector('[data-carousel-prev]')?.addEventListener('click', () => show(i - 1));
    root.querySelector('[data-carousel-next]')?.addEventListener('click', () => show(i + 1));
    dots.forEach((d, idx) => d.addEventListener('click', () => show(idx)));
    setInterval(() => { if (slides.length > 1) show(i + 1); }, 8000);
})();
</script>
@endpush
@endsection
