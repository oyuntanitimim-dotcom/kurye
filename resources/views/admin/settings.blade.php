@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-4">Ayarlar</h1>
<p class="text-slate-600 text-sm mb-6 max-w-2xl">
    Uygulama: <strong>{{ config('app.name') }}</strong>
    @if(config('app.env'))
        · Ortam: <code class="text-xs bg-slate-100 px-1 rounded">{{ config('app.env') }}</code>
    @endif
</p>

<div class="max-w-2xl space-y-6 text-sm">
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="font-medium text-slate-800 mb-2">Yapılandırma</h2>
        <p class="text-slate-600">Ortam değişkenleri ve gizli anahtarlar <code class="text-xs bg-slate-100 px-1">.env</code> dosyasından yönetilir. Değişiklik sonrası önbellek için:</p>
        <pre class="mt-2 text-xs bg-slate-900 text-slate-100 p-3 rounded-lg overflow-x-auto">php artisan config:clear</pre>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="font-medium text-slate-800 mb-2">Kuyruk ve bildirimler</h2>
        <p class="text-slate-600 mb-2">In-app bildirimler kuyruk üzerinden işlenir. Yerelde çalıştırmak için:</p>
        <pre class="text-xs bg-slate-900 text-slate-100 p-3 rounded-lg overflow-x-auto">php artisan queue:work</pre>
        <p class="text-slate-500 text-xs mt-2">Bağlantı: <code class="bg-slate-100 px-1">{{ config('queue.default') }}</code></p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="font-medium text-slate-800 mb-2">Hızlı bağlantılar</h2>
        <ul class="list-disc list-inside space-y-1 text-amber-800">
            <li><a href="{{ route('admin.firms.index') }}" class="hover:underline">Kurye şirketleri</a></li>
            <li><a href="{{ route('admin.orders.index') }}" class="hover:underline">Siparişler</a></li>
            <li><a href="{{ route('admin.reports.index') }}" class="hover:underline">Raporlar</a></li>
            <li><a href="{{ route('notifications.index') }}" class="hover:underline">Bildirimler</a></li>
        </ul>
    </div>
</div>
@endsection
