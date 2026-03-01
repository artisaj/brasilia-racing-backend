<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $incomingRequestId = trim((string) $request->header('X-Request-Id'));
        $requestId = $incomingRequestId !== '' ? Str::limit($incomingRequestId, 64, '') : (string) Str::uuid();

        $request->attributes->set('request_id', $requestId);

        Log::withContext([
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
