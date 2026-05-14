@props([
    'searchPlaceholder' => 'Ara…',
    'searchName' => 'q',
    'searchValue' => null,
    'perPageName' => 'per_page',
    'perPageOptions' => [10, 25, 30, 50],
    'currentPerPage' => 30,
    'showSubmit' => true,
    'withSearchIcon' => false,
])

<div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
    <div class="flex min-w-0 flex-1 flex-col gap-2 sm:max-w-xl sm:flex-row sm:items-end">
        <div class="min-w-0 flex-1">
            <label for="panel-toolbar-search" class="mb-1 block text-xs font-medium text-slate-500">Ara</label>
            <div class="relative">
                @if ($withSearchIcon)
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                @endif
                <input
                    id="panel-toolbar-search"
                    type="search"
                    name="{{ $searchName }}"
                    value="{{ $searchValue }}"
                    placeholder="{{ $searchPlaceholder }}"
                    class="w-full rounded-lg border border-slate-200 bg-white py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent {{ $withSearchIcon ? 'pl-10 pr-3' : 'px-3' }}"
                >
            </div>
        </div>
        @if ($showSubmit)
            <button type="submit" class="shrink-0 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50">
                Ara
            </button>
        @endif
    </div>
    <div class="flex items-end gap-2">
        <div>
            <label for="panel-toolbar-per-page" class="mb-1 block text-xs font-medium text-slate-500">Sayfa başına satır</label>
            <select
                id="panel-toolbar-per-page"
                name="{{ $perPageName }}"
                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent"
                onchange="this.form.requestSubmit()"
            >
                @foreach ($perPageOptions as $opt)
                    <option value="{{ $opt }}" @selected((int) $currentPerPage === (int) $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
