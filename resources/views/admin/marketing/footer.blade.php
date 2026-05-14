@extends('layouts.admin')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold text-slate-900">Footer</h1>
    <form method="post" action="{{ route('admin.marketing.footer.columns.store') }}">
        @csrf
        <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Sütun ekle</button>
    </form>
</div>

@if(session('ok'))
    <p class="mb-4 rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('ok') }}</p>
@endif

<form method="post" action="{{ route('admin.marketing.footer.update') }}" class="space-y-8">
    @csrf
    @method('PUT')

    @foreach($columns as $colIdx => $column)
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <input type="hidden" name="columns[{{ $colIdx }}][id]" value="{{ $column->id }}" />
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div class="flex-1">
                    <label class="mb-1 block text-xs text-slate-500">Sütun başlığı</label>
                    <input name="columns[{{ $colIdx }}][heading]" value="{{ old('columns.'.$colIdx.'.heading', $column->heading) }}" class="w-full max-w-md rounded border border-slate-300 px-2 py-1.5 text-sm" />
                </div>
            </div>

            <h3 class="mb-2 text-xs font-semibold uppercase text-slate-500">Bağlantılar</h3>
            <div class="space-y-2">
                @foreach($column->links as $linkIdx => $link)
                    <div class="flex flex-wrap items-end gap-2 rounded border border-slate-100 p-2">
                        <input type="hidden" name="columns[{{ $colIdx }}][links][{{ $linkIdx }}][id]" value="{{ $link->id }}" />
                        <input name="columns[{{ $colIdx }}][links][{{ $linkIdx }}][label]" value="{{ $link->label }}" placeholder="Etiket" class="rounded border border-slate-300 px-2 py-1 text-sm" />
                        <input name="columns[{{ $colIdx }}][links][{{ $linkIdx }}][url]" value="{{ $link->url }}" placeholder="URL" class="min-w-[12rem] flex-1 rounded border border-slate-300 px-2 py-1 text-sm" />
                        <input type="hidden" name="columns[{{ $colIdx }}][links][{{ $linkIdx }}][sort_order]" value="{{ $link->sort_order }}" />
                        <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="columns[{{ $colIdx }}][links][{{ $linkIdx }}][open_in_new_tab]" value="1" @checked($link->open_in_new_tab) /> yeni sekme</label>
                    </div>
                @endforeach
                @php $newIdx = $column->links->count(); @endphp
                <div class="flex flex-wrap items-end gap-2 rounded border border-dashed border-slate-200 p-2">
                    <input type="hidden" name="columns[{{ $colIdx }}][links][{{ $newIdx }}][id]" value="" />
                    <input name="columns[{{ $colIdx }}][links][{{ $newIdx }}][label]" value="" placeholder="Yeni — etiket" class="rounded border border-slate-300 px-2 py-1 text-sm" />
                    <input name="columns[{{ $colIdx }}][links][{{ $newIdx }}][url]" value="" placeholder="URL" class="min-w-[12rem] flex-1 rounded border border-slate-300 px-2 py-1 text-sm" />
                    <input type="hidden" name="columns[{{ $colIdx }}][links][{{ $newIdx }}][sort_order]" value="{{ $newIdx }}" />
                </div>
            </div>
        </div>
    @endforeach

    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">Kaydet</button>
</form>

<div class="mt-8 space-y-2 border-t border-slate-200 pt-6">
    <p class="text-xs font-semibold uppercase text-slate-500">Sütun sil</p>
    @foreach($columns as $column)
        <form method="post" action="{{ route('admin.marketing.footer.columns.destroy', $column) }}" class="inline-flex items-center gap-2" onsubmit="return confirm('Bu sütun ve tüm bağlantıları silinsin mi?');">
            @csrf
            @method('DELETE')
            <span class="text-sm text-slate-600">{{ $column->heading ?: 'Başlıksız' }}</span>
            <button type="submit" class="text-sm text-red-600 hover:underline">Sil</button>
        </form>
    @endforeach
</div>

<p class="mt-6"><a href="{{ route('admin.marketing.index') }}" class="text-sm text-amber-700 hover:underline">← CMS özet</a></p>
@endsection
