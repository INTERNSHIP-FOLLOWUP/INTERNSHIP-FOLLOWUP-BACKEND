<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// 1. PUBLIC ROUTES (No Authentication Required - Guest Access)
Route::group(['prefix' => 'auth'], function () {
    // BE-10: Register Endpoint (Validated by RegisterRequest)
    Route::post('/register', [AuthController::class, 'register']);

    // BE-11: Login Endpoint (Validated by LoginRequest)
    Route::post('/login', [AuthController::class, 'login']);

    // BE-13: Forgot-Password Stub Placeholder
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
});

// 2. PROTECTED ROUTES (Requires Laravel Sanctum Authentication)
Route::middleware('auth:sanctum')->group(function () {

    Route::group(['prefix' => 'auth'], function () {
        // Fetch Current Authenticated User State
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // BE-14: Update Profile Endpoint
        Route::put('/profile/update', [AuthController::class, 'updateProfile']);

        // BE-12: Logout Endpoint
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
