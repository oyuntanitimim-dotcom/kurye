@extends('layouts.app')

@section('body')
<div class="min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-xl font-semibold mb-6">Müşteri girişi</h1>
        <form method="post" action="{{ route('shop.login.store') }}" class="space-y-4">
            @csrf
            <input type="email" name="email" placeholder="E-posta" value="{{ old('email', 'musteri@demo.local') }}" required class="w-full rounded border border-slate-300 px-3 py-2">
            <input type="password" name="password" value="password" required class="w-full rounded border border-slate-300 px-3 py-2">
            <button type="submit" class="w-full rounded-lg bg-slate-900 py-2 text-white">Giriş</button>
        </form>
        @if($errors->any())
            <p class="mt-4 text-sm text-red-600">{{ $errors->first() }}</p>
        @endif
    </div>
</div>
@endsection
