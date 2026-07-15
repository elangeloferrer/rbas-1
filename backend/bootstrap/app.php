<?php

use App\Exceptions\Auth\AccountInactiveException;
use App\Exceptions\Auth\EmailNotVerifiedException;
use App\Exceptions\Auth\ExpiredResetTokenException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\InvalidResetTokenException;
use App\Exceptions\Auth\RoleNotFoundException;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForceJsonResponse;
use App\Providers\RepositoryServiceProvider;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // // Sanctum SPA cookie support — must be in the web group
        // $middleware->web(append: [
        //     \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        // ]);

        $middleware->api(prepend: [
            // Force Accept: application/json on all API requests
            ForceJsonResponse::class,
            // Sanctum SPA cookie support for Vue frontend
            EnsureFrontendRequestsAreStateful::class,
            // Apply throttle on all API routes
            ThrottleRequests::class.':api',
        ]);

        // Named middleware aliases for use in routes
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'active' => EnsureUserIsActive::class,
        ]);
    })
    ->withProviders([
        RepositoryServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        // Return JSON for all API exceptions
        $exceptions->shouldRenderJsonWhen(
            fn ($request) => $request->is('api/*') || $request->expectsJson()
        );

        // Customize rate limit response with role-specific message and retry_after seconds
        $exceptions->renderable(function (
            ThrottleRequestsException $e,
            $request
        ) {
            $retryAfter = (int) ($e->getHeaders()['Retry-After'] ?? 60);

            $message = match (true) {
                $request->is('api/admin/*') => 'Too many admin login attempts. Please wait before retrying.',
                $request->is('api/merchant/*') => 'Too many merchant requests. Please wait before retrying.',
                $request->is('api/register'),
                $request->is('api/login') => 'Too many requests. Please wait before retrying.',
                default => 'Too many requests. Please wait before retrying.',
            };

            return response()->json([
                'success' => false,
                'message' => $message,
                'retry_after' => $retryAfter,
            ], 429)->header('Retry-After', $retryAfter);
        });

        // Prevent "Route [login] not defined" — return 401 for unauthenticated API requests
        $exceptions->renderable(function (
            AuthenticationException $e,
            $request
        ) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
        });

        // Map custom auth exceptions to JSON responses
        $exceptions->renderable(function (
            InvalidCredentialsException $e,
            $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        });

        $exceptions->renderable(function (
            AccountInactiveException $e,
            $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        });

        $exceptions->renderable(function (
            EmailNotVerifiedException $e,
            $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        });

        $exceptions->renderable(function (
            RoleNotFoundException $e,
            $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        });

        $exceptions->renderable(function (
            InvalidResetTokenException $e,
            $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        });

        $exceptions->renderable(function (
            ExpiredResetTokenException $e,
            $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        });
    })
    ->create();
