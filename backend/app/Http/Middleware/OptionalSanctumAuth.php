<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OptionalSanctumAuth
{
    public function handle(Request $request, Closure $next)
    {
        // If Authorization: Bearer <token> is present and valid,
        // this will set the authenticated user for this request.
        Auth::shouldUse('sanctum');
        Auth::guard('sanctum')->setRequest($request);

        // Touch the guard so it resolves the user if possible
        Auth::guard('sanctum')->user();

        return $next($request);
    }
}
