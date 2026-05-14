@extends('admin.layout')

@section('content')
<h1>Paketler</h1>
<p class="text-slate-600">Bu bölüm henüz tanımlı değil. Fiyatlandırma ve paketler için şimdilik kurye şirketi kayıtlarındaki ücret alanlarını kullanın.</p>
<p><a class="nav-link" href="{{ route('admin.firms.index') }}" style="display:inline-block">Kurye şirketleri →</a></p>
@endsection
