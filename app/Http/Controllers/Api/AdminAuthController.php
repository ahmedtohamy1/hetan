<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    private const ADMIN_PASSWORD = 'admin123'; // You can change this or load from config

    /**
     * Admin login.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if ($request->password === self::ADMIN_PASSWORD) {
            // Generate a simple token (in production, use proper JWT or Sanctum)
            $token = Hash::make(now()->timestamp . self::ADMIN_PASSWORD);

            // Store token in cache for 24 hours
            Cache::put('admin_token_' . $token, true, now()->addHours(24));

            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
            ]);
        }

        return response()->json([
            'message' => 'Invalid password',
        ], 401);
    }

    /**
     * Admin logout.
     */
    public function logout(): JsonResponse
    {
        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Check if admin is authenticated.
     */
    public function check(Request $request): JsonResponse
    {
        // Simple token check (in production, use proper middleware)
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json(['authenticated' => false], 401);
        }

        return response()->json(['authenticated' => true]);
    }
}
