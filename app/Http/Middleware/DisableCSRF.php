<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableCSRF
{
    public function handle(Request $request, Closure $next): Response
    {
        // Disable CSRF for all requests
        return $next($request);
    }
}
