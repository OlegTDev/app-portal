<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\HandleInertiaRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'auth:portal' => \Illuminate\Auth\Middleware\Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        // Sync employees from Active Directory
        // Frequency is configurable via config('ad-sync.frequency') or AD_SYNC_FREQUENCY env variable
        // Options: 'hourly', 'daily', or a cron expression like "0 2 * * *"
        $frequency = config('ad-sync.frequency', 'daily');

        match ($frequency) {
            'hourly' => $schedule->command('ad:sync')->hourly(),
            'daily' => $schedule->command('ad:sync')->daily(),
            default => $schedule->command('ad:sync')->cron($frequency),
        };
    })->create();
