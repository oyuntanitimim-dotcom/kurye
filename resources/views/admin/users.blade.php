@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Kullanıcılar</h1>

<form method="get" action="{{ route('admin.users.index') }}" class="mb-6 flex flex-wrap gap-3 items-end text-sm">
    <div>
        <label class="block text-slate-600 mb-1">Rol</label>
        <select name="role_id" class="rounded border border-slate-300 px-3 py-2 min-w-[10rem]">
            <option value="">Tümü</option>
            @foreach($roles as $r)
                <option value="{{ $r->id }}" @selected(($filters['role_id'] ?? '') == $r->id)>{{ $r->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-slate-600 mb-1">Kurye şirketi</label>
        <select name="firm_id" class="rounded border border-slate-300 px-3 py-2 min-w-[12rem]">
            <option value="">Tümü</option>
            <option value="none" @selected(($filters['firm_id'] ?? '') === 'none')>Platform (firma yok)</option>
            @foreach($firms as $f)
                <option value="{{ $f->id }}" @selected(($filters['firm_id'] ?? '') == $f->id)>{{ $f->name }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white">Filtrele</button>
    <a href="{{ route('admin.users.index') }}" class="text-amber-700 hover:underline py-2">Sıfırla</a>
</form>

<table class="w-full text-sm">
    <thead><tr class="border-b text-left text-slate-500"><th class="py-2">Ad</th><th>E-posta</th><th>Rol</th><th>Kurye şirketi</th></tr></thead>
    <tbody>
        @foreach($users as $u)
            <tr class="border-b border-slate-100">
                <td class="py-2">{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td>{{ $u->role?->name }}</td>
                <td>{{ $u->firm?->name ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
