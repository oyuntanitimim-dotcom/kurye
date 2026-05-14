@extends('layouts.firm')

@section('content')
<h1 class="text-2xl font-semibold mb-6">{{ $title }}</h1>
<form method="post" action="{{ route('firm.campaigns.store') }}" class="max-w-xl space-y-4">
    @include('firm.campaigns._form', ['campaign' => null])
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Kaydet</button>
    <a href="{{ route('firm.campaigns.index') }}" class="ml-2 text-sm text-slate-600 hover:underline">İptal</a>
</form>
@endsection
