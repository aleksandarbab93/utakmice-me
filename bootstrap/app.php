<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Plain fetch() from the service-worker push toggle doesn't carry a
        // CSRF token, and doesn't need to: these endpoints identify a
        // browser by its push subscription, not by session. The match-detail
        // intake is the same shape of thing from the other direction — another
        // machine's artisan command, holding a shared secret, with no session
        // to take a token from.
        $middleware->validateCsrfTokens(except: ['api/push/*', 'api/detalji-meca']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
