<section id="sss" class="mt-12 scroll-mt-28">

    @if(!empty($data['title']))

        <h2 class="text-xl font-semibold text-red-950">{{ $data['title'] }}</h2>

    @endif

    <dl class="mt-6 space-y-4">

        @foreach($data['items'] ?? [] as $faq)

            <div class="rounded-lg border border-red-100 bg-white p-4 ring-1 ring-red-950/[0.03]">

                <dt class="font-medium text-red-950">{{ $faq['q'] ?? '' }}</dt>

                <dd class="mt-2 text-sm text-red-900/75">{{ $faq['a'] ?? '' }}</dd>

            </div>

        @endforeach

    </dl>

</section>

