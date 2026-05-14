@extends('layouts.courier')

@section('content')
<h1 class="text-2xl font-semibold mb-6">Teslimatlar</h1>
<p class="text-sm text-slate-600 mb-4">Tamamlanan teslimatlarınız.</p>
<div class="overflow-x-auto text-sm rounded-xl border border-slate-200 bg-white max-w-3xl">
    <table class="w-full">
        <thead><tr class="border-b text-left text-slate-500"><th class="py-2 px-4">#</th><th>Restoran</th><th>Müşteri</th><th>Ödeme</th><th>Tutar</th><th>Tarih</th></tr></thead>
        <tbody>
            @forelse($orders as $o)
                <tr class="border-b border-slate-100">
                    <td class="py-2 px-4">{{ $o->id }}</td>
                    <td>{{ $o->restaurant?->name }}</td>
                    <td>{{ $o->customer?->name }}</td>
                    <td class="text-slate-700">{{ $o->paymentMethodLabel() }}</td>
                    <td>{{ number_format((float) $o->total_price, 2) }} ₺</td>
                    <td>{{ $o->updated_at?->format('d.m.Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 px-4 text-slate-500 text-center">Henüz teslimat yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
