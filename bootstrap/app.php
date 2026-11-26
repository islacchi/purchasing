<?php

use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\EnsureSessionIsActive;
use App\Http\Middleware\RejectTruncatedFormPost;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            RejectTruncatedFormPost::class,
        ]);

        $middleware->alias([
            'session.active' => EnsureSessionIsActive::class,
            'module' => EnsureModuleAccess::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Retention policy for the activity trail: keep one year of
        // entries. Requires a cron/task entry that runs
        // `php artisan schedule:run` (see README).
        $schedule->command('activity:prune --days=365')->weekly();

        // Desktop toast for approaching/overdue deliveries — same rules
        // as the bell icon, but fires even when nobody has the app open.
        // Task Scheduler still needs to call `php artisan schedule:run`
        // on Windows (see README).
        $schedule->command('notify:deadlines')->everyFourHours();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
