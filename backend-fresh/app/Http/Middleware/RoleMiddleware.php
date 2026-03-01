<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// ============================================================
// ROLE MIDDLEWARE
//
// Usage in routes:
//   Route::middleware(['jwt', 'role:admin'])->group(...)
//
// Protects admin-only routes from regular farmers
// ============================================================
class RoleMiddleware
{
    /**
     * @param $role  - The required role (e.g., 'admin', 'farmer')
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = auth()->user();

        if (!$user || $user->role !== $role) {
            return response()->json([
                'success' => false,
                'message' => "Access denied. This action requires the '{$role}' role."
            ], 403);
        }

        return $next($request);
    }
}
