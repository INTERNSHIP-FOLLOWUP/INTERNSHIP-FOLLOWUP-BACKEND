<?php

// use App\Http\Controllers\AuthController;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
// use App\Http\Controllers\AuthController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Authentication Routes (Guest Access)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // BE-10: Register Endpoint
    Route::post('/register', [AuthController::class, 'register']);

    // BE-11: Login Endpoint
    Route::post('/login', [AuthController::class, 'login']);

    // BE-13: Forgot-Password Stub Placeholder
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
});

/*
|--------------------------------------------------------------------------
| Protected Authentication Routes (Requires Sanctum Token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    // Fetch Current Authenticated User State
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // BE-14: Update Profile Endpoint
    Route::put('/profile/update', [AuthController::class, 'updateProfile']);

    // BE-12: Logout Endpoint
    Route::post('/logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| Admin Management Routes (Requires Sanctum Token & Admin Role)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});