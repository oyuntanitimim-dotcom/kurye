@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-2">{{ $title }}</h1>
<p class="text-sm text-slate-600 mb-6">
    <span class="font-medium text-slate-800">{{ $restaurant->firm?->name ?? '—' }}</span>
    kurye şirketi — firma kaydı ve <strong>/restoran</strong> paneli giriş bilgileri (şifre sıfırlama).
</p>
@include('firm.restaurants._edit_form', [
    'restaurant' => $restaurant,
    'restaurantAdmin' => $restaurantAdmin,
    'formAction' => route('admin.restaurants.update', $restaurant),
    'cancelUrl' => route('admin.restaurants.index'),
    'adminFirmsForSelect' => $adminFirmsForSelect,
])
@endsection
