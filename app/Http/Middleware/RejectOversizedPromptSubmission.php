<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectOversizedPromptSubmission
{
    private const MaximumBytes = 32768;

    public function handle(Request $request, Closure $next): Response
    {
        if ((int) $request->header('Content-Length', 0) > self::MaximumBytes || strlen($request->getContent()) > self::MaximumBytes) {
            return response()->json(['message' => 'The submission is too large.'], 413);
        }

        return $next($request);
    }
}
