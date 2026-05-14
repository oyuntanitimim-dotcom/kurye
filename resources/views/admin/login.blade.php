@extends('layouts.app')

@section('body')
<div class="min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-xl font-semibold mb-1">Süper yönetici girişi</h1>
        <p class="text-sm text-slate-500 mb-6">admin@kurye.local / password</p>
        <form method="post" action="{{ route('admin.login.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm text-slate-600">E-posta</label>
                <input type="email" name="email" value="{{ old('email', 'admin@kurye.local') }}" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="text-sm text-slate-600">Şifre</label>
                <input type="password" name="password" value="password" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <button type="submit" class="w-full rounded-lg bg-slate-900 py-2 text-white">Giriş</button>
        </form>
        @if($errors->any())
            <p class="mt-4 text-sm text-red-600">{{ $errors->first() }}</p>
        @endif
    </div>
</div>
@endsection
