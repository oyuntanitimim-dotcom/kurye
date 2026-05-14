@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-6">{{ $title }}</h1>
<form method="post" action="{{ route('admin.coupons.update', $coupon) }}" class="max-w-xl space-y-4">
    @method('PUT')
    @include('admin.coupons._form', ['coupon' => $coupon])
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Güncelle</button>
    <a href="{{ route('admin.coupons.index') }}" class="ml-2 text-sm text-slate-600 hover:underline">İptal</a>
</form>
@endsection
