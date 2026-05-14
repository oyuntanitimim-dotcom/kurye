@extends('admin.layout')

@section('content')
<h1>Yeni şirket</h1>
<p class="text-slate-600" style="margin-bottom:1rem">Bu sayfa eski kiracı modülüne aitti. Kurye şirketi oluşturmak için güncel formu kullanın.</p>
<p><a class="nav-link" href="{{ route('admin.firms.create') }}" style="display:inline-block">Kurye şirketi oluştur →</a></p>
@endsection
