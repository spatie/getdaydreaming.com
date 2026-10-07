<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectOversizedReleaseUpload
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maximumBytes = $request->is('api/releases/appcast') ? 262144 : 2048;

        if ((int) $request->header('Content-Length', 0) > $maximumBytes
            || strlen($request->getContent()) > $maximumBytes) {
            return response()->json(['message' => 'The release upload is too large.'], 413);
        }

        return $next($request);
    }
}
