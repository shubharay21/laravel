<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {

            if ($e instanceof TokenInvalidException) {
                return response()->json(['error' => 'Token is invalid'], 401);
            }

            if ($e instanceof TokenExpiredException) {
                return response()->json(['error' => 'Token has expired'], 401);
            }

            return response()->json(['error' => 'Authorization Token not found'], 401);
        }

        return $next($request);
    }
}
