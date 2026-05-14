@extends('layouts.firm')

@section('content')
<p class="mb-4"><a href="{{ route('firm.orders.index') }}" class="text-sm text-amber-700 hover:underline">← Sipariş listesi</a></p>
<h1 class="text-2xl font-semibold mb-6">{{ $title }}</h1>
@include('partials.order-detail', ['order' => $order, 'showFirm' => true])
@endsection
