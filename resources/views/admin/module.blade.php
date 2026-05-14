@extends('admin.layout')

@section('content')
<h1>Eski şirket listesi</h1>
<p class="text-slate-600">Kiracı veritabanı tabanlı liste kaldırıldı. Tüm kurye şirketleri tek veritabanında <code>firms</code> tablosunda.</p>
<p><a class="nav-link" href="{{ route('admin.firms.index') }}" style="display:inline-block">Kurye şirketleri →</a></p>
@endsection
