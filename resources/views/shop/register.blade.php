@extends('layouts.app')

@section('body')
<div class="min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-xl font-semibold mb-2">Kayıt ol</h1>
        <p class="text-sm text-slate-500 mb-6">{{ $firm->name }}</p>
        <form method="post" action="{{ route('shop.register.store') }}" class="space-y-4">
            @csrf
            <input type="text" name="name" placeholder="Ad soyad" value="{{ old('name') }}" required class="w-full rounded border border-slate-300 px-3 py-2">
            <input type="email" name="email" placeholder="E-posta" value="{{ old('email') }}" required class="w-full rounded border border-slate-300 px-3 py-2">
            <input type="text" name="phone" placeholder="Telefon" value="{{ old('phone') }}" class="w-full rounded border border-slate-300 px-3 py-2">
            <input type="password" name="password" required class="w-full rounded border border-slate-300 px-3 py-2" placeholder="Şifre">
            <input type="password" name="password_confirmation" required class="w-full rounded border border-slate-300 px-3 py-2" placeholder="Şifre tekrar">
            <button type="submit" class="w-full rounded-lg bg-slate-900 py-2 text-white">Kayıt ol</button>
        </form>
        @if($errors->any())
            <p class="mt-4 text-sm text-red-600">{{ $errors->first() }}</p>
        @endif
    </div>
</div>
@endsection
