@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => 'Ara…',
    'allLabel' => 'Tümü',
    'minWidthClass' => 'min-w-[14rem]',
    'autoSubmit' => true,
])

@php
    $id = 'ss-'.md5($name.'|'.($attributes->get('id') ?? '').'|'.spl_object_id($attributes));
    $selected = (string) ($value ?? '');
@endphp

<div {{ $attributes->merge(['class' => '']) }}>
    @if($label)
        <label for="{{ $id }}-select" class="mb-1 block text-xs font-medium text-slate-500">{{ $label }}</label>
    @endif
    <input
        type="search"
        id="{{ $id }}-search"
        placeholder="{{ $placeholder }}"
        class="{{ $minWidthClass }} mb-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent"
        autocomplete="off"
    />
    <select
        name="{{ $name }}"
        id="{{ $id }}-select"
        class="{{ $minWidthClass }} w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-panel-accent focus:outline-none focus:ring-1 focus:ring-panel-accent"
        @if($autoSubmit) onchange="this.form.requestSubmit()" @endif
        data-searchable-select="1"
        data-search-input-id="{{ $id }}-search"
    >
        <option value="">{{ $allLabel }}</option>
        @foreach($options as $opt)
            <option value="{{ $opt['value'] }}" @selected((string) $opt['value'] === $selected)>{{ $opt['label'] }}</option>
        @endforeach
    </select>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                function normalize(s) {
                    return (s || '').toString().toLocaleLowerCase('tr-TR').trim();
                }

                function hook(select) {
                    const inputId = select.getAttribute('data-search-input-id');
                    const input = inputId ? document.getElementById(inputId) : null;
                    if (!input) return;

                    const options = Array.from(select.options);
                    const allOption = options[0];

                    function applyFilter() {
                        const needle = normalize(input.value);
                        options.forEach((opt, idx) => {
                            if (idx === 0) return; // "Tümü"
                            const show = needle === '' || normalize(opt.textContent).includes(needle);
                            opt.hidden = !show;
                        });

                        // Seçili değer gizlendiyse kullanıcıyı şaşırtmamak için aramayı temizleme
                        // Yapmıyoruz; sadece liste daralır.
                    }

                    input.addEventListener('input', applyFilter);
                    input.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') {
                            input.value = '';
                            applyFilter();
                            input.blur();
                        }
                    });

                    // İlk yükte uygulanır (geri tuşu / query string ile)
                    if (select.value && select.value !== allOption.value) {
                        // seçili option'u görünür tutmak adına aramayı boş bırakıyoruz
                    }
                    applyFilter();
                }

                document.addEventListener('DOMContentLoaded', () => {
                    document.querySelectorAll('select[data-searchable-select="1"]').forEach(hook);
                });
            })();
        </script>
    @endpush
@endonce
