@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-6">{{ $title }}</h1>
<form method="post" action="{{ route('admin.campaigns.update', $campaign) }}" class="max-w-xl space-y-4">
    @method('PUT')
    @include('admin.campaigns._form', ['campaign' => $campaign])
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Güncelle</button>
    <a href="{{ route('admin.campaigns.index') }}" class="ml-2 text-sm text-slate-600 hover:underline">İptal</a>
</form>
@endsection
