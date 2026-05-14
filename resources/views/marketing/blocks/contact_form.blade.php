<section id="iletisim" class="mt-20 scroll-mt-28">

    @if($errors->any())

        <div class="mx-auto mb-6 max-w-xl rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">

            {{ $errors->first() }}

        </div>

    @endif

    <div class="mx-auto max-w-2xl text-center">

        @if(!empty($data['title']))

            <h2 class="text-2xl font-bold tracking-tight text-red-950 sm:text-3xl">{{ $data['title'] }}</h2>

        @endif

        @if(!empty($data['subtitle']))

            <p class="mt-3 text-red-900/75">{{ $data['subtitle'] }}</p>

        @endif

        @if(config('marketing.public_contact_email'))

            <p class="mt-2 text-sm text-red-800/70">

                <a href="mailto:{{ config('marketing.public_contact_email') }}" class="font-medium text-red-700 hover:underline">{{ config('marketing.public_contact_email') }}</a>

            </p>

        @endif

    </div>



    <div class="mx-auto mt-10 max-w-xl rounded-2xl border border-red-100 bg-white p-6 shadow-sm ring-1 ring-red-950/[0.04] sm:p-8">

        <form method="post" action="{{ route('marketing.contact.store') }}" class="space-y-4">

            @csrf

            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />



            <div>

                <label for="mkt-name" class="mb-1 block text-sm font-medium text-red-950">Ad Soyad</label>

                <input id="mkt-name" name="name" type="text" required value="{{ old('name') }}" class="w-full rounded-lg border border-red-200 px-3 py-2 text-sm text-red-950 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" />

            </div>

            <div>

                <label for="mkt-email" class="mb-1 block text-sm font-medium text-red-950">E-posta</label>

                <input id="mkt-email" name="email" type="email" required value="{{ old('email') }}" class="w-full rounded-lg border border-red-200 px-3 py-2 text-sm text-red-950 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" />

            </div>

            <div class="grid gap-4 sm:grid-cols-2">

                <div>

                    <label for="mkt-phone" class="mb-1 block text-sm font-medium text-red-950">Telefon</label>

                    <input id="mkt-phone" name="phone" type="text" value="{{ old('phone') }}" class="w-full rounded-lg border border-red-200 px-3 py-2 text-sm text-red-950 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" />

                </div>

                <div>

                    <label for="mkt-company" class="mb-1 block text-sm font-medium text-red-950">Şirket</label>

                    <input id="mkt-company" name="company" type="text" value="{{ old('company') }}" class="w-full rounded-lg border border-red-200 px-3 py-2 text-sm text-red-950 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" />

                </div>

            </div>

            <div>

                <label for="mkt-message" class="mb-1 block text-sm font-medium text-red-950">Mesaj</label>

                <textarea id="mkt-message" name="message" rows="4" required class="w-full rounded-lg border border-red-200 px-3 py-2 text-sm text-red-950 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500">{{ old('message') }}</textarea>

            </div>

            <button type="submit" class="w-full rounded-xl bg-red-600 py-3 text-sm font-semibold text-white shadow-md shadow-red-900/20 ring-1 ring-red-600/30 transition hover:bg-red-700">Gönder</button>

        </form>

    </div>

</section>

