@extends('layouts.admin')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-2xl font-semibold text-slate-900">Slider</h1>
    <a href="{{ route('admin.marketing.slides.create') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">Yeni slayt</a>
</div>

@if(session('ok'))
    <p class="mb-4 rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('ok') }}</p>
@endif

<table class="w-full text-sm">
    <thead>
        <tr class="border-b text-left text-slate-500">
            <th class="py-2">Başlık</th>
            <th>Sıra</th>
            <th>Aktif</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($slides as $slide)
            <tr class="border-b border-slate-100">
                <td class="py-2">{{ $slide->title }}</td>
                <td>{{ $slide->sort_order }}</td>
                <td>{{ $slide->active ? 'Evet' : 'Hayır' }}</td>
                <td class="text-right">
                    <a href="{{ route('admin.marketing.slides.edit', $slide) }}" class="text-amber-700 hover:underline">Düzenle</a>
                    <form method="post" action="{{ route('admin.marketing.slides.destroy', $slide) }}" class="inline" onsubmit="return confirm('Silinsin mi?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ml-2 text-red-600 hover:underline">Sil</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<p class="mt-6"><a href="{{ route('admin.marketing.index') }}" class="text-sm text-amber-700 hover:underline">← CMS özet</a></p>
@endsection
