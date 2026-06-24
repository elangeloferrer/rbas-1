<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        apiPrefix: 'api',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA cookie support — must be in the web group
        $middleware->web(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Apply throttle + JSON header enforcement on all API routes
        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
        ]);

        // Named middleware aliases for use in routes
        $middleware->alias([
            'role'   => EnsureUserHasRole::class,
            'active' => EnsureUserIsActive::class,
        ]);
    })
    ->withProviders([
        App\Providers\RepositoryServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        // Return JSON for all API exceptions
        $exceptions->shouldRenderJsonWhen(
            fn($request) => $request->is('api/*') || $request->expectsJson()
        );
    })
    ->create();
