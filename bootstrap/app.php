<?php

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
        // Ensure CORS headers are sent for every API response
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        // EnsureFrontendRequestsAreStateful is intentionally NOT added here.
        // The React app uses Bearer token auth, not cookie/session auth.
        // That middleware enforces CSRF token checks on stateful domains,
        // which causes 419 errors on every login/register request.

        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
