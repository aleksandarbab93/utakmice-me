<?php

use Illuminate\Database\QueryException;
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
        // Prepended so a cached page is answered before the session starts
        // and before any route binding runs — a hit costs one cache read and
        // touches the database not at all.
        $middleware->prependToGroup('web', \App\Http\Middleware\CachePage::class);

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

        // A database that is momentarily gone — out of connections, out of
        // memory, restarting — is not a broken page. Google reads a 500 as
        // "this page doesn't work" and eventually drops it from the index; it
        // reads a 503 with Retry-After as "come back later" and keeps it. Only
        // connection-level codes are treated this way: a malformed query still
        // throws a 500, so real bugs stay loud.
        $exceptions->render(function (QueryException $e, Request $request) {
            if (! in_array((int) $e->getCode(), [2002, 2003, 2006, 1040, 1203], true)) {
                return null;
            }

            return response()
                ->view('errors.503', [], 503)
                ->header('Retry-After', '120');
        });
    })->create();
