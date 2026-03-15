<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Broadcast;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // Enregistrer la route broadcasting/auth avec le middleware JWT
            Broadcast::routes(['middleware' => ['auth:api']]);
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'verified.api' => \App\Http\Middleware\EnsureEmailIsVerifiedApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
