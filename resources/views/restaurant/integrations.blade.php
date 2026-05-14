@extends('layouts.restaurant')

@section('content')
<div class="max-w-4xl space-y-6">
    @if(session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold mb-2">{{ $title }}</h1>
            <p class="text-sm text-slate-600 max-w-2xl">
                Platformların resmi API dokümantasyonuna göre kimlik bilgisi alıp kendi uçlarınızı çağırırsınız.
                Webhook ve token yönetimi için aşağıdan bir entegrasyon seçin veya yeni ekleyin.
            </p>
        </div>
        @if(!$editingProvider)
            <button type="button" id="open-integration-dialog" class="shrink-0 rounded-lg bg-panel-accent px-4 py-2.5 text-sm font-medium text-white hover:opacity-95">
                Entegrasyon ekle
            </button>
        @endif
    </div>

    <details class="rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 group">
        <summary class="cursor-pointer list-none px-4 py-3 font-medium text-slate-900 flex items-center justify-between gap-2 [&::-webkit-details-marker]:hidden">
            <span>Teknik özet: normalize gövde ve başlıklar</span>
            <span class="text-xs font-normal text-slate-500 group-open:hidden">Göster</span>
            <span class="text-xs font-normal text-slate-500 hidden group-open:inline">Gizle</span>
        </summary>
        <div class="border-t border-slate-200 px-4 pb-4 pt-2 space-y-2">
            <ul class="list-disc pl-5 space-y-1">
                <li><code class="text-xs bg-white px-1 rounded">external_order_id</code> (zorunlu)</li>
                <li><code class="text-xs bg-white px-1 rounded">items[]</code> → <code class="text-xs bg-white px-1 rounded">product_id</code> veya <code class="text-xs bg-white px-1 rounded">external_sku</code> + <code class="text-xs bg-white px-1 rounded">quantity</code></li>
                <li><code class="text-xs bg-white px-1 rounded">customer_name</code>, <code class="text-xs bg-white px-1 rounded">customer_phone</code>, isteğe bağlı <code class="text-xs bg-white px-1 rounded">delivery_fee</code>, <code class="text-xs bg-white px-1 rounded">payment_method</code></li>
            </ul>
            <p class="text-slate-600">İstek başlığı: <code class="text-xs bg-white px-1 rounded">X-Integration-Token</code>. İsteğe bağlı: <code class="text-xs bg-white px-1 rounded">X-Firm-Id: {{ $restaurant->firm_id }}</code></p>
        </div>
    </details>

    @if($editingProvider)
        @include('restaurant.integrations._provider_panel', [
            'providerKey' => $editingProvider,
            'meta' => $providers[$editingProvider],
            'connections' => $connections,
            'mapsByProvider' => $mapsByProvider,
            'products' => $products,
        ])
    @else
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-semibold text-slate-800 mb-3">Kayıtlı entegrasyonlar</h2>
            @if($configuredProviderKeys->isEmpty())
                <p class="text-sm text-slate-500 mb-4">Henüz yapılandırılmış pazar yeri yok. <strong>Entegrasyon ekle</strong> ile başlayın.</p>
            @else
                <ul class="grid gap-3 sm:grid-cols-2">
                    @foreach($configuredProviderKeys as $key)
                        @php
                            $meta = $providers[$key];
                            $conn = $connections->get($key);
                            $mapCount = $mapsByProvider->get($key, collect())->count();
                            $active = $conn?->is_active ?? false;
                        @endphp
                        <li class="flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900 truncate">{{ $meta['label'] }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    @if($active)<span class="text-emerald-700">Aktif</span>@else<span class="text-slate-500">Pasif</span>@endif
                                    @if($mapCount > 0) · {{ $mapCount }} eşleme @endif
                                </p>
                            </div>
                            <a href="{{ route('restaurant.integrations.index', ['duzenle' => $key]) }}" class="shrink-0 rounded-md bg-white border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-800 hover:bg-slate-100">
                                Düzenle
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <dialog id="integration-picker-dialog" class="max-w-lg w-[calc(100%-2rem)] rounded-xl border border-slate-200 bg-white p-0 shadow-xl backdrop:bg-slate-900/40">
        <div class="border-b border-slate-100 px-5 py-4 flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-900">Entegrasyon seçin</h2>
            <form method="dialog">
                <button type="submit" class="rounded p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-800" aria-label="Kapat">✕</button>
            </form>
        </div>
        <ul class="max-h-[min(24rem,70vh)] overflow-y-auto p-2">
            @foreach($providers as $providerKey => $meta)
                <li>
                    <a href="{{ route('restaurant.integrations.index', ['duzenle' => $providerKey]) }}" class="flex flex-col gap-0.5 rounded-lg px-3 py-3 text-left hover:bg-slate-50">
                        <span class="font-medium text-slate-900">{{ $meta['label'] }}</span>
                        <span class="text-xs text-slate-500 line-clamp-2">{{ $meta['description'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </dialog>
</div>
@push('scripts')
<script>
document.getElementById('open-integration-dialog')?.addEventListener('click', function () {
    document.getElementById('integration-picker-dialog')?.showModal();
});
</script>
@endpush
@endsection
