<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // REMOVE THIS LINE - Don't apply globally
        // $middleware->append(\App\Http\Middleware\VerifyVapiWebhook::class);
        
        // You can register it here for optional use later
        $middleware->alias([
            'verify.vapi' => \App\Http\Middleware\VerifyVapiWebhook::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // Send reminders every minute
        $schedule->command('reminders:send')->everyMinute();

        // Clean old logs every day at 2 AM
        $schedule->command('logs:clean')->dailyAt('02:00');
    })
    ->create();