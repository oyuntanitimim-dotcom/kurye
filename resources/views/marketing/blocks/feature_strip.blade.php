@php

    $rows = [];

    if (! empty($data['items']) && is_array($data['items'])) {

        $rows = $data['items'];

    } elseif (! empty($data['feature_strip']) && is_array($data['feature_strip'])) {

        $rows = $data['feature_strip'];

    }

    $rows = array_values(array_filter($rows, static function ($row): bool {

        if (! is_array($row)) {

            return false;

        }



        return ($row['title'] ?? '') !== '' || ($row['body'] ?? '') !== '';

    }));

@endphp

@if($rows !== [])

    <div id="ozellikler" class="scroll-mt-28 grid gap-3 sm:grid-cols-3 sm:gap-4">

        @foreach ($rows as $row)

            <div class="group relative rounded-2xl border border-red-100 bg-white p-5 shadow-sm ring-1 ring-red-950/[0.04] transition duration-200 hover:-translate-y-0.5 hover:border-red-200 hover:shadow-lg sm:rounded-3xl sm:p-6">

                <div class="grid grid-cols-[auto_1fr] items-start gap-x-3">
                    <div class="mt-0.5 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-red-600 text-white ring-1 ring-red-700/30 transition group-hover:bg-red-700 sm:h-12 sm:w-12">
                        @include('marketing.partials.feature-icon', ['index' => $loop->index, 'tone' => 'inverse'])
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold leading-snug text-red-950 sm:text-lg">{{ $row['title'] ?? '' }}</h3>
                        @if(! empty($row['body']))
                            <p class="mt-1.5 text-sm leading-relaxed text-red-900/75">{{ $row['body'] }}</p>
                        @endif
                    </div>
                </div>

            </div>

        @endforeach

    </div>

@endif

