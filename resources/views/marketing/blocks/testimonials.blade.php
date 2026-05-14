<section id="referanslar" class="scroll-mt-28">
    <div id="yorumlar" class="sr-only" aria-hidden="true"></div>
    @if(! empty($data['title']))
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-red-600/90">Referanslar</p>
            <h2 class="mt-2 text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">{{ $data['title'] }}</h2>
        </div>
    @endif

    <div class="mt-10 grid gap-6 lg:grid-cols-3">
        @foreach($data['items'] ?? [] as $t)
            <blockquote class="relative flex h-full flex-col rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm ring-1 ring-zinc-950/[0.02]">
                <p class="text-sm italic leading-relaxed text-zinc-700">“{{ $t['quote'] ?? '' }}”</p>
                <footer class="mt-auto border-t border-zinc-100 pt-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-zinc-200 bg-zinc-100 text-xs font-bold text-zinc-500" aria-hidden="true">
                            {{ \Illuminate\Support\Str::substr(trim((string) ($t['author'] ?? '?')), 0, 1) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-zinc-900">{{ $t['author'] ?? '' }}</p>
                            @if(! empty($t['role']))
                                <p class="mt-0.5 text-xs text-zinc-500">{{ $t['role'] }}</p>
                            @endif
                        </div>
                        @if(! empty($t['company']))
                            <span class="shrink-0 rounded border border-zinc-200 bg-zinc-50 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-zinc-500">{{ $t['company'] }}</span>
                        @endif
                    </div>
                </footer>
            </blockquote>
        @endforeach
    </div>
</section>
