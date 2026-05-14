{{-- Slider içi: gerçek uygulama ekran görüntüsü --}}
@php
    $src = asset('images/marketing/mobile-app.png');
@endphp
<div class="relative w-full max-w-none">
    <div class="absolute -inset-2 rounded-2xl bg-gradient-to-tr from-red-600/25 via-transparent to-red-400/10 opacity-90 blur-xl" aria-hidden="true"></div>
    <figure class="relative flex items-stretch justify-center overflow-hidden rounded-2xl border border-red-800/40 bg-gradient-to-br from-red-950 to-[#0c0202] shadow-2xl ring-1 ring-red-950/50">
        <img
            src="{{ $src }}"
            alt="Mobil uygulama ekranı"
            class="h-full w-full object-cover object-center"
            loading="eager"
            width="900"
            height="1600"
        />
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-red-950/35 via-transparent to-transparent" aria-hidden="true"></div>
    </figure>
</div>
