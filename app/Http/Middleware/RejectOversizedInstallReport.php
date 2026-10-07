<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectOversizedInstallReport
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((int) $request->header('Content-Length', 0) > 2048 || strlen($request->getContent()) > 2048) {
            return response()->json(['message' => 'The report is too large.'], 413);
        }

        return $next($request);
    }
}
