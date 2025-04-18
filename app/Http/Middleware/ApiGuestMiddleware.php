<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiGuestMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Check if Authorization header with Bearer token exists
        $authHeader = $request->header('Authorization');

        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            try {
                if (JWTAuth::parseToken()->authenticate()) {
                    return response()->json(['message' => 'You are already authenticated.'], 403);
                }
            } catch (\Exception $e) {
                // Token is invalid or expired, so treat as guest
            }
        }

        return $next($request);
    }
}
