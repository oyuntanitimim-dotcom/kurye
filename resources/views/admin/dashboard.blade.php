@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Gösterge Paneli</h1>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @foreach($stats as $s)
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase text-slate-500">{{ $s['label'] }}</div>
            <div class="text-2xl font-bold text-slate-800">{{ $s['value'] }}</div>
        </div>
    @endforeach
</div>
@endsection
