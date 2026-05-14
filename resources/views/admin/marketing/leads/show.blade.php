@extends('layouts.admin')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.marketing.leads.index') }}" class="text-sm text-amber-700 hover:underline">← Tüm talepler</a>
    <h1 class="mt-2 text-2xl font-semibold text-slate-900">Talep #{{ $lead->id }}</h1>
    <p class="text-sm text-slate-500">{{ $lead->created_at->format('d.m.Y H:i') }}</p>
</div>

<dl class="max-w-2xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 text-sm shadow-sm">
    <div>
        <dt class="font-medium text-slate-500">Ad</dt>
        <dd class="mt-1 text-slate-900">{{ $lead->name }}</dd>
    </div>
    <div>
        <dt class="font-medium text-slate-500">E-posta</dt>
        <dd class="mt-1"><a href="mailto:{{ $lead->email }}" class="text-amber-700 hover:underline">{{ $lead->email }}</a></dd>
    </div>
    @if($lead->phone)
        <div>
            <dt class="font-medium text-slate-500">Telefon</dt>
            <dd class="mt-1">{{ $lead->phone }}</dd>
        </div>
    @endif
    @if($lead->company)
        <div>
            <dt class="font-medium text-slate-500">Şirket</dt>
            <dd class="mt-1">{{ $lead->company }}</dd>
        </div>
    @endif
    <div>
        <dt class="font-medium text-slate-500">Mesaj</dt>
        <dd class="mt-1 whitespace-pre-wrap text-slate-800">{{ $lead->message }}</dd>
    </div>
    @if($lead->ip_address)
        <div>
            <dt class="font-medium text-slate-500">IP</dt>
            <dd class="mt-1 font-mono text-xs text-slate-600">{{ $lead->ip_address }}</dd>
        </div>
    @endif
</dl>
@endsection
