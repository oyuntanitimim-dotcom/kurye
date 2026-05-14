<section class="mt-16 rounded-2xl border border-red-100 bg-red-50/50 px-6 py-10 ring-1 ring-red-950/[0.04]">

    @if(!empty($data['title']))

        <h2 class="text-center text-lg font-semibold text-red-950">{{ $data['title'] }}</h2>

    @endif

    <div class="mt-8 flex flex-wrap items-center justify-center gap-3 sm:gap-4">

        @foreach($data['logos'] ?? [] as $logo)

            @php $name = is_array($logo) ? ($logo['name'] ?? '') : $logo; @endphp

            @if($name !== '')

                <span class="rounded-full border border-red-100 bg-white px-4 py-2 text-sm font-medium text-red-900 shadow-sm">{{ $name }}</span>

            @endif

        @endforeach

    </div>

</section>

