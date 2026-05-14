@extends('layouts.firm')

@section('content')
<h1 class="text-2xl font-semibold mb-6">{{ $title }}</h1>
@include('firm.restaurants._edit_form', [
    'restaurant' => $restaurant,
    'restaurantAdmin' => $restaurantAdmin,
    'formAction' => route('firm.restaurants.update', $restaurant),
    'cancelUrl' => route('firm.restaurants.index'),
    'adminFirmsForSelect' => null,
])
@endsection
