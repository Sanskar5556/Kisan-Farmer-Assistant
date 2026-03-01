<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

// ============================================================
// AUTH CONTROLLER
// Handles Register, Login, Logout, and Get Current User
//
// HOW JWT WORKS (for beginners):
// 1. User sends email+password → We verify → We send back a "token"
// 2. For every future request, user sends that token in header:
//    Authorization: Bearer <token>
// 3. Laravel reads the token, extracts the user, and processes request
// ============================================================
class AuthController extends Controller
{
    /**
     * Register a new farmer (or admin)
     * POST /api/auth/register
     */
    public function register(Request $request)
    {
        // Step 1: Validate all inputs
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users', // Must not already be registered
            'password' => 'required|string|min:6|confirmed', // confirmed = needs password_confirmation field
            'phone'    => 'nullable|string|max:20',
            'state'    => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'role'     => 'nullable|in:farmer,admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        // Step 2: Create the user in database
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password), // Never store plain text passwords!
            'phone'    => $request->phone,
            'state'    => $request->state,
            'district' => $request->district,
            'role'     => $request->role ?? 'farmer', // Default to farmer
        ]);

        // Step 3: Generate JWT token for immediate login
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful!',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    /**
     * Login with email + password
     * POST /api/auth/login
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            // JWTAuth attempts to authenticate and returns a token
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ], 401);
            }

            $user = auth()->user();

            return response()->json([
                'success' => true,
                'message' => 'Login successful!',
                'token'   => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60, // seconds
                'user'    => $user,
            ]);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token. Please try again.'
            ], 500);
        }
    }

    /**
     * Logout - invalidate the token
     * POST /api/auth/logout
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout'
            ], 500);
        }
    }

    /**
     * Get the currently logged-in user's profile
     * GET /api/auth/me
     */
    public function me()
    {
        $user = auth()->user();
        return response()->json([
            'success' => true,
            'user'    => $user,
        ]);
    }

    /**
     * Refresh the JWT token (extend session)
     * POST /api/auth/refresh
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'token'   => $newToken,
            ]);
        } catch (JWTException $e) {
            return response()->json(['success' => false, 'message' => 'Token refresh failed'], 401);
        }
    }
}
