<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrackApiTime
{
    public function handle(Request $request, Closure $next)
    {
        // Store request start time for later calculation
        $request->server->set('REQUEST_TIME_FLOAT', microtime(true));

        return $next($request);
    }
}
