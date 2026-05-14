@extends('layouts.app')

@section('body')
    <x-panel.shell brand="Kurye şirketi">
        <x-slot:sidebar>
            @include('components.panel.nav-firm')
        </x-slot:sidebar>
        <x-slot:header>
            @auth
                <div class="flex min-w-0 flex-1 items-center justify-end gap-2 sm:gap-3">
                    @include('partials.firm-header-watch')
                    <span class="truncate text-sm text-slate-600">{{ auth()->user()->name }}</span>
                </div>
            @endauth
        </x-slot:header>

        @yield('content')
    </x-panel.shell>
    @include('partials.firm-courier-request-alert')
@endsection
