<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReleaseToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('services.release.token');
        $providedToken = $request->bearerToken();

        if (! is_string($configuredToken) || strlen($configuredToken) < 32
            || ! is_string($providedToken) || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
