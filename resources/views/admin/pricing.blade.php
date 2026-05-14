@extends('admin.layout')

@section('content')
<h1>Fiyatlandırma</h1>
<p class="text-slate-600">Merkezi fiyat listesi henüz yok. Platform ve restoran başına ücretler kurye şirketi düzenleme ekranında.</p>
<p><a class="nav-link" href="{{ route('admin.firms.index') }}" style="display:inline-block">Kurye şirketleri →</a></p>
@endsection
