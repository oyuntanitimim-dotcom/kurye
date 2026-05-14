@php
    $steps = $data['steps'] ?? $data['items'] ?? [];
    if (! is_array($steps)) {
        $steps = [];
    }
@endphp
<section id="nasil-calisir" class="scroll-mt-28 rounded-2xl border border-zinc-200/80 bg-white px-4 py-14 shadow-sm sm:px-8">
    <div class="mx-auto max-w-2xl text-center">
        @if(! empty($data['title']))
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">{{ $data['title'] }}</h2>
        @endif
        @if(! empty($data['subtitle']))
            <p class="mt-3 text-zinc-600">{{ $data['subtitle'] }}</p>
        @endif
    </div>
    @if($steps !== [])
        <div class="mx-auto mt-12 max-w-5xl">
            <div class="flex flex-col items-stretch gap-4 lg:flex-row lg:items-start lg:justify-between" role="list">
                @foreach ($steps as $i => $step)
                    @if(is_array($step))
                        @if($i > 0)
                            <div class="flex shrink-0 items-center justify-center py-1 lg:flex-col lg:justify-start lg:pt-6" aria-hidden="true">
                                <span class="text-2xl font-light leading-none text-red-300 lg:rotate-0">→</span>
                            </div>
                        @endif
                        <div class="flex flex-1 flex-col items-center text-center" role="listitem">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full border-2 border-red-100 bg-red-50 text-lg font-black text-red-600 shadow-sm ring-4 ring-white">
                                {{ $step['number'] ?? (string) ($i + 1) }}
                            </div>
                            <h3 class="mt-4 text-sm font-bold text-zinc-900 sm:text-base">{{ $step['title'] ?? '' }}</h3>
                            @if(! empty($step['body']))
                                <p class="mt-1.5 max-w-[220px] text-xs leading-relaxed text-zinc-600 sm:text-sm">{{ $step['body'] }}</p>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</section>
