<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

// ============================================================
// JWT MIDDLEWARE
//
// Every API route (except login/register) must pass through this.
// It checks: "Is the token valid? Is the user real?"
// If not → reject with 401 Unauthorized
// ============================================================
class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // Try to authenticate using JWT token from Authorization header
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 401);
            }

        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired. Please login again.'
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid.'
            ], 401);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided. Add: Authorization: Bearer <your_token>'
            ], 401);
        }

        return $next($request);
    }
}
