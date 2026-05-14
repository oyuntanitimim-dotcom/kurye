@extends('shop.layout')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Restoranlar</h1>
<div class="space-y-3">
    @foreach($restaurants as $r)
        <a href="{{ route('shop.restaurant', $r) }}" class="block rounded-xl border border-slate-200 bg-white p-4 hover:border-amber-400">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-medium">{{ $r->name }}</span>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $r->business_type->label() }}</span>
            </div>
            <div class="text-sm text-slate-500">{{ $r->phone }}</div>
        </a>
    @endforeach
</div>
<div class="mt-6">{{ $restaurants->links() }}</div>
@endsection
