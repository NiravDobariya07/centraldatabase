<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Constants\AppConstants;
use Illuminate\Support\Facades\Cache;

class RequestResponseLoggerMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Log request if the "request" category is enabled
        customLog(AppConstants::LOG_CATEGORIES['REQUEST'], '📝 Incoming Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'payload' => $request->all(),
        ]);

        $response = $next($request);

        // Log response if the "response" category is enabled
        customLog(AppConstants::LOG_CATEGORIES['RESPONSE'], '🚀 Outgoing Response', [
            'status' => $response->status(),
            'content' => $response->getContent(),
        ]);

        return $response;
    }
}
