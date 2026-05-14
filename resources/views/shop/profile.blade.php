@extends('shop.layout')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Profilim</h1>
<p class="mb-2"><strong>{{ $user->name }}</strong></p>
<p class="text-slate-600 text-sm mb-8">{{ $user->email }}</p>

<h2 class="text-lg font-medium mb-3">Adres ekle</h2>
<form method="post" action="{{ route('shop.profile.address') }}" class="max-w-md space-y-3 mb-10">
    @csrf
    <input type="text" name="title" placeholder="Başlık (Ev, iş)" required class="w-full rounded border border-slate-300 px-3 py-2">
    <textarea name="address" rows="3" placeholder="Adres" required class="w-full rounded border border-slate-300 px-3 py-2"></textarea>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Kaydet</button>
</form>

<h2 class="text-lg font-medium mb-3">Kayıtlı adresler</h2>
<ul class="space-y-2 text-sm">
    @foreach($user->addresses as $a)
        <li class="rounded border border-slate-100 p-3"><strong>{{ $a->title }}</strong><br>{{ $a->address }}</li>
    @endforeach
</ul>
@endsection
