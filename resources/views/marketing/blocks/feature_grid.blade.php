<section id="ozellikler" class="{{ ! empty($data['title']) ? 'mt-0' : 'mt-6' }} scroll-mt-28">
    @if(! empty($data['title']))
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">{{ $data['title'] }}</h2>
            @if(! empty($data['subtitle']))
                <p class="mt-3 text-zinc-600">{{ $data['subtitle'] }}</p>
            @endif
        </div>
    @endif
    <div class="{{ ! empty($data['title']) ? 'mt-12' : 'mt-0' }} grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($data['items'] ?? [] as $item)
            <div class="group rounded-2xl border border-zinc-200/90 bg-white p-6 shadow-[0_4px_24px_-4px_rgba(0,0,0,0.08)] ring-1 ring-zinc-950/[0.03] transition duration-200 hover:-translate-y-0.5 hover:border-red-200 hover:shadow-lg sm:p-7">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-red-50 ring-1 ring-red-100 transition group-hover:bg-red-100">
                        @include('marketing.partials.feature-icon', ['index' => $loop->index])
                    </div>
                    <h3 class="text-base font-extrabold leading-snug text-zinc-900 sm:text-lg">{{ $item['title'] ?? '' }}</h3>
                </div>
                <p class="mt-4 text-sm leading-relaxed text-zinc-600">{{ $item['body'] ?? '' }}</p>
            </div>
        @endforeach
    </div>
</section>
