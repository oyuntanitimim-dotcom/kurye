@extends('layouts.app')

@section('body')
    <x-panel.shell brand="Kurye">
        <x-slot:sidebar>
            @include('components.panel.nav-courier')
        </x-slot:sidebar>
        <x-slot:header>
            @auth
                <span class="text-sm text-slate-600">{{ auth()->user()->name }}</span>
            @endauth
        </x-slot:header>

        @yield('content')
    </x-panel.shell>
@endsection
