<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\LoginRequest; // 1. Inject your validation class
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    // BE-10: Register endpoint placeholder
    public function register(Request $request)
    {
        // Leave empty for now
    }

    //BE-11: Login endpoint placeholder

    public function login(Request $request)
    {
        // Find the user by email
        $user = User::where('email', $request->email)->first();

        // Verify user exists and check if the password matches the database hash
        if (!$user || !Hash::check($request->password, $user->password)) {
            // Throwing a ValidationException automatically returns a standardized 422 JSON error response
            throw ValidationException::withMessages([
                'email' => ['The credentials you provided are incorrect.'],
            ]);
        }

        // Generate a new secure API token text string for this user
        $token = $user->createToken('auth_token')->plainTextToken;

        // Return a successful JSON payload
        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar,
            ]
        ], 200);
    }
    // BE-12: Logout endpoint placeholder
    public function logout(Request $request)
    {
        // 1. Target the specific PersonalAccessToken used to authorize this request and delete it
        $request->user()->currentAccessToken()->delete();

        // 2. Return a standard RESTful success message
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully. Token has been revoked.'
        ], 200);
    }
    // BE-13: Forgot-password stub placeholder
    public function forgotPassword(Request $request)
    
    {
        // 1. Basic validation to ensure an email structure is submitted by the client
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // 2. Return an HTTP 202 Accepted response. 
        return response()->json([
            'status' => 'stub_placeholder',
            'message' => 'Password reset recovery link request received. (Feature placeholder stub)',
            'target_email' => $request->email,
            'notice' => 'Mail provider driver integration will be connected in a future sprint.'
        ], 202);
    }
    // BE-14: Update profile endpoint placeholder
    public function updateProfile(Request $request)
    {
        // Leave empty for now
    }
}
