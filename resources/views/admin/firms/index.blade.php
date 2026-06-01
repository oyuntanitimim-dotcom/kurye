@extends('layouts.admin')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold">Kurye şirketleri</h1>
    <button type="button" data-dialog-open="firm-create" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Yeni kurye şirketi</button>
</div>

<dialog id="firm-create" class="w-[min(720px,calc(100vw-2rem))] rounded-xl p-0 shadow-xl">
    <form method="dialog" class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <p class="text-base font-semibold text-slate-900">Yeni kurye şirketi</p>
        <button type="submit" data-dialog-close="firm-create" class="rounded-md px-2 py-1 text-slate-500 hover:bg-slate-100">Kapat</button>
    </form>

    <form method="post" action="{{ route('admin.firms.store') }}" class="px-5 py-4">
        @csrf
        <input type="hidden" name="from_modal" value="1">

        @if($errors->any() && old('from_modal') == 1)
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <p class="font-medium">Kaydedilemedi</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="block text-sm text-slate-600 mb-1">Kurye şirketi adı</label>
                <input name="name" value="{{ old('name') }}" required class="w-full rounded border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="block text-sm text-slate-600 mb-1">Şehir</label>
                <input name="city" value="{{ old('city') }}" class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm text-slate-600 mb-1">İlçe</label>
                <input name="district" value="{{ old('district') }}" class="w-full rounded border border-slate-300 px-3 py-2">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm text-slate-600 mb-1">Domain (ör. localhost)</label>
                <input name="domain" value="{{ old('domain') }}" required class="w-full rounded border border-slate-300 px-3 py-2">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm text-slate-600 mb-1">Paket başı kontör (adet)</label>
                <input type="number" step="1" min="1" name="credits_per_order_override" value="{{ old('credits_per_order_override') }}" placeholder="{{ $globalCreditsPerOrder }}" class="w-full rounded border border-slate-300 px-3 py-2">
                <p class="text-xs text-slate-500 mt-1">Her kurye atamasında bu firmadan düşülecek kontör. Boş = genel varsayılan ({{ $globalCreditsPerOrder }} kontör).</p>
            </div>
        </div>

        <hr class="my-5 border-slate-200">

        <p class="text-sm font-medium text-slate-700 mb-3">Kurye şirketi yöneticisi</p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="block text-sm text-slate-600 mb-1">Ad soyad</label>
                <input name="admin_name" value="{{ old('admin_name') }}" required class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm text-slate-600 mb-1">E-posta</label>
                <input type="email" name="admin_email" value="{{ old('admin_email') }}" required class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm text-slate-600 mb-1">Şifre</label>
                <input type="password" name="admin_password" required class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2">
            <button type="button" data-dialog-close="firm-create" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-slate-700 text-sm hover:bg-slate-50">Vazgeç</button>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Kaydet</button>
        </div>
    </form>
</dialog>

<form method="get" action="{{ route('admin.firms.index') }}" class="mb-6 flex flex-wrap gap-3 items-end text-sm">
    <div>
        <label class="block text-slate-600 mb-1">Ara (ad / domain)</label>
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="…" class="rounded border border-slate-300 px-3 py-2 w-64">
    </div>
    <div>
        <label class="block text-slate-600 mb-1">Durum</label>
        <select name="status" class="rounded border border-slate-300 px-3 py-2">
            <option value="">Tümü</option>
            <option value="active" @selected(($filters['status'] ?? '') === 'active')>active</option>
            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>inactive</option>
        </select>
    </div>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white">Filtrele</button>
    <a href="{{ route('admin.firms.index') }}" class="text-amber-700 hover:underline py-2">Sıfırla</a>
</form>

<table class="w-full text-sm border-collapse">
    <thead>
        <tr class="border-b border-slate-200 text-left text-slate-500">
            <th class="py-2">Ad</th>
            <th>Domain</th>
            <th>Paket başı kontör</th>
            <th>Durum</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($firms as $f)
            <tr class="border-b border-slate-100">
                <td class="py-2 font-medium">{{ $f->name }}</td>
                <td>{{ $f->domain }}</td>
                <td>{{ $f->credits_per_order_override ?? $globalCreditsPerOrder }} <span class="text-xs text-slate-400">kontör</span></td>
                <td>{{ $f->status }}</td>
                <td class="space-x-3 whitespace-nowrap">
                    <a class="text-slate-600 hover:underline" href="{{ route('admin.firms.show', $f) }}">Detay</a>
                    <a class="text-amber-700 hover:underline" href="{{ route('admin.firms.edit', $f) }}">Düzenle</a>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $firms->links() }}</div>

@if($errors->any() && old('from_modal') == 1)
    <script>
        window.__openFirmCreateDialog = true;
    </script>
@endif
@endsection
