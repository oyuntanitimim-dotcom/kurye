@once
    <style>
        @keyframes marketing-shimmer {
            0% { background-position: 200% 50%; }
            100% { background-position: -200% 50%; }
        }
        @keyframes marketing-underline-sweep {
            0% { transform: translateX(-60%); opacity: .0; }
            20% { opacity: .9; }
            50% { opacity: 1; }
            80% { opacity: .9; }
            100% { transform: translateX(60%); opacity: .0; }
        }
        .marketing-title-shimmer {
            background-size: 240% auto;
            animation: marketing-shimmer 3.8s linear infinite;
        }
        .marketing-underline-sweep {
            animation: marketing-underline-sweep 2.8s ease-in-out infinite;
        }
        @media (prefers-reduced-motion: reduce) {
            .marketing-title-shimmer,
            .marketing-underline-sweep { animation: none !important; }
        }
    </style>
@endonce

<section class="-mt-2 pt-1 sm:-mt-3">

    @if(!empty($data['title']))
        <div class="relative mx-auto w-fit text-center">
            <p class="text-xs font-bold uppercase tracking-[0.22em]">
                <span class="marketing-title-shimmer bg-gradient-to-r from-red-900 via-red-500 to-red-900 bg-clip-text text-transparent">{{ $data['title'] }}</span>
            </p>
            <div class="pointer-events-none absolute -bottom-2 left-1/2 h-[3px] w-28 -translate-x-1/2 overflow-hidden rounded-full bg-red-200/60" aria-hidden="true">
                <div class="marketing-underline-sweep absolute left-1/2 top-0 h-full w-10 -translate-x-1/2 rounded-full bg-gradient-to-r from-transparent via-red-600 to-transparent"></div>
            </div>
        </div>
    @endif

    @if(!empty($data['subtitle']))

        <p class="mx-auto mt-1 max-w-2xl text-center text-sm text-red-900/75">{{ $data['subtitle'] }}</p>

    @endif

    <div class="mt-4 flex flex-wrap justify-center gap-2 sm:gap-3">

        @foreach($data['platforms'] ?? [] as $p)

            @php $name = is_array($p) ? ($p['name'] ?? '') : $p; @endphp

            @if($name !== '')

                <span class="inline-flex items-center rounded-full border border-red-100 bg-white px-4 py-2 text-sm font-medium text-red-900 shadow-sm ring-1 ring-red-950/[0.04] transition hover:border-red-300 hover:bg-red-50/80">{{ $name }}</span>

            @endif

        @endforeach

    </div>

</section>

