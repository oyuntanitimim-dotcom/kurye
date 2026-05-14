@extends('layouts.app')

@section('body')
<div class="min-h-screen flex items-center justify-center p-6 bg-slate-100">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-semibold text-slate-900 mb-1">Giriş</h1>
        <p class="text-sm text-slate-500 mb-6">Hesabınızla giriş yapın; rolünüze uygun panele yönlendirilirsiniz.</p>
        <form method="post" action="{{ route('login.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">E-posta veya kullanıcı adı</label>
                <input type="text" name="email" value="{{ old('email', 'admin@kurye.local') }}" required maxlength="190" autocomplete="username"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Şifre</label>
                <input type="password" name="password" value="password" required autocomplete="current-password"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none">
            </div>
            <button type="submit" class="w-full rounded-lg bg-slate-900 py-3 text-white font-medium hover:bg-slate-800 transition">
                Giriş yap
            </button>
        </form>
        <p class="mt-6 flex flex-col items-center gap-2 text-center text-sm text-slate-600 sm:flex-row sm:flex-wrap sm:justify-center sm:gap-x-1 sm:gap-y-0">
            <span class="inline-flex flex-wrap items-center justify-center gap-x-1">
                Müşteri misiniz?
                <a href="{{ route('shop.register') }}" class="text-amber-700 font-medium hover:underline">Kayıt ol</a>
            </span>
            <span class="hidden sm:inline" aria-hidden="true">·</span>
            <a href="{{ route('shop.home') }}" class="text-slate-500 hover:underline">Mağazaya dön</a>
        </p>
        @if($errors->any())
            <p class="mt-4 text-sm text-red-600 text-center">{{ $errors->first() }}</p>
        @endif

        @if(config('dev_login.show_test_credentials'))
            <div class="mt-8 rounded-lg border border-dashed border-amber-300/80 bg-amber-50/90 p-4 text-left">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-900/80 mb-2">Test girişleri</p>
                <p class="text-xs text-amber-900/70 mb-3">Şifre (hepsi): <code class="rounded bg-white px-1.5 py-0.5 font-mono text-amber-950">{{ config('dev_login.password_hint') }}</code></p>
                <ul class="space-y-1.5 text-xs text-slate-700">
                    @foreach(config('dev_login.accounts') as $row)
                        <li class="flex justify-between gap-2 border-b border-amber-100/80 pb-1.5 last:border-0 last:pb-0">
                            <span class="text-slate-500 shrink-0">{{ $row['label'] }}</span>
                            <code class="font-mono text-[11px] text-slate-800 break-all text-right">{{ $row['email'] }}</code>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 text-[10px] text-slate-500">Listeyi değiştirmek için: <code class="font-mono">config/dev_login.php</code></p>
                @if(config('app.env') !== 'production')
                    <p class="mt-2 text-[10px] text-slate-600">Giriş reddediliyorsa (migrate sonrası): <code class="font-mono rounded bg-white px-1">php artisan kurye:repair-demo-logins</code> veya <code class="font-mono rounded bg-white px-1">php artisan db:seed</code></p>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
