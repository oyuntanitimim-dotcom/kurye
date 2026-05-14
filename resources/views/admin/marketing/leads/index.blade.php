@extends('layouts.admin')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-2xl font-semibold text-slate-900">İletişim talepleri</h1>
    <a href="{{ route('admin.marketing.index') }}" class="text-sm text-amber-700 hover:underline">← CMS</a>
</div>

<table class="w-full text-sm">
    <thead>
        <tr class="border-b text-left text-slate-500">
            <th class="py-2">#</th>
            <th>Ad</th>
            <th>E-posta</th>
            <th>Tarih</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse($leads as $lead)
            <tr class="border-b border-slate-100 {{ $lead->read ? '' : 'bg-amber-50/40' }}">
                <td class="py-2">{{ $lead->id }}</td>
                <td class="font-medium text-slate-900">{{ $lead->name }}</td>
                <td>{{ $lead->email }}</td>
                <td class="text-slate-500">{{ $lead->created_at->format('d.m.Y H:i') }}</td>
                <td class="text-right">
                    <a href="{{ route('admin.marketing.leads.show', $lead) }}" class="text-amber-700 hover:underline">Aç</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="py-8 text-center text-slate-500">Henüz talep yok.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="mt-6">{{ $leads->links() }}</div>
@endsection
