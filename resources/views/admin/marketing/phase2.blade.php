@extends('layouts.admin')

@section('content')
<h1 class="mb-4 text-2xl font-semibold text-slate-900">{{ $title }}</h1>
<p class="max-w-2xl text-slate-600">
    Gelişmiş sürükle-bırak bölüm oluşturucu, çoklu dil ve revizyon geçmişi arayüzü bu planın <strong>Faz‑2</strong> kapsamında ayrı bir PR olarak ele alınacaktır.
    Faz‑1’de blok tabanlı JSON editörü, önizleme ve yayınlama tamamlandı.
</p>
<p class="mt-6"><a href="{{ route('admin.marketing.index') }}" class="text-sm text-amber-700 hover:underline">← CMS özet</a></p>
@endsection
