<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Reject the request if the authenticated user does not have the required role.
     *
     *@param\Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $hasRole = $request->user()
            ->roles()
            ->where('name', $role)
            ->exists();

        if (! $hasRole) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have the required role.',
            ], 403);
        }

        return $next($request);
    }
}
