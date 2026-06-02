<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('login'));

        $trusted = env('TRUSTED_PROXIES', '*');
        $middleware->trustProxies(at: $trusted === '*' ? '*' : array_filter(array_map('trim', explode(',', (string) $trusted))));

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\EnsureAdminAuthenticated::class,
            'firm.auth' => \App\Http\Middleware\EnsureFirmAdminAuthenticated::class,
            'restaurant.auth' => \App\Http\Middleware\EnsureRestaurantAuthenticated::class,
            'courier.auth' => \App\Http\Middleware\EnsureCourierAuthenticated::class,
            'firm.resolve' => \App\Http\Middleware\ResolveFirmFromDomain::class,
            'firm.from_auth' => \App\Http\Middleware\ResolveFirmFromAuth::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
            'active.account' => \App\Http\Middleware\EnsureActiveAccount::class,
            'api.tenant' => \App\Http\Middleware\EnsureApiTenantBoundaries::class,
            'web.tenant' => \App\Http\Middleware\EnsureWebTenantBoundaries::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        if (class_exists(\Laravel\Horizon\Horizon::class)) {
            $schedule->command('horizon:snapshot')->everyFiveMinutes();
        }
    })
    ->create();
