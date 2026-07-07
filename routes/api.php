<?php

<<<<<<< HEAD
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
=======
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
>>>>>>> fe0bc9c05155d5d77de1282a07e303f9a64213fd
});
