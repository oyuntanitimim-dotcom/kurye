@extends('layouts.app')

@section('body')
<header class="sticky top-0 z-10 border-b border-slate-200 bg-white/95 backdrop-blur-sm supports-[backdrop-filter]:bg-white/90">
    <div class="mx-auto flex max-w-5xl flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:py-4">
        <a href="{{ route('shop.home') }}" class="touch-manipulation truncate text-lg font-semibold text-slate-900 sm:text-base">{{ $firm->name ?? 'Mağaza' }}</a>
        <nav class="flex flex-wrap gap-x-4 gap-y-2 text-sm sm:justify-end">
            <a class="touch-manipulation py-1.5 text-slate-600 hover:text-slate-900" href="{{ route('shop.restaurants') }}">Restoranlar</a>
            <a class="touch-manipulation py-1.5 text-slate-600 hover:text-slate-900" href="{{ route('shop.cart') }}">Sepetim</a>
            @auth
                <a class="touch-manipulation py-1.5 text-slate-600 hover:text-slate-900" href="{{ route('shop.orders') }}">Siparişlerim</a>
                <a class="touch-manipulation py-1.5 text-slate-600 hover:text-slate-900" href="{{ route('shop.profile') }}">Profilim</a>
                <form method="post" action="{{ route('logout') }}" class="inline-flex items-center">@csrf<button type="submit" class="touch-manipulation py-1.5 text-amber-700 hover:underline">Çıkış</button></form>
            @else
                <a class="touch-manipulation py-1.5 text-slate-600 hover:text-slate-900" href="{{ route('login') }}">Giriş</a>
                <a class="touch-manipulation py-1.5 text-amber-700 hover:underline" href="{{ route('shop.register') }}">Kayıt</a>
            @endauth
        </nav>
    </div>
</header>
<main class="mx-auto max-w-5xl px-4 py-6 sm:py-8">
    @if(session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif
    @yield('content')
</main>
@endsection
