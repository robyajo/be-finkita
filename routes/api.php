<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public routes with auth rate limiter (5/min - brute force protection)
Route::middleware('throttle:auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->name('api.v1.register');
    Route::post('login', [AuthController::class, 'login'])->name('api.v1.login');
    Route::post('refresh', [AuthController::class, 'refresh'])->name('api.v1.refresh');
});

// Google OAuth (no throttle for OAuth flow)
Route::prefix('auth')->group(function (): void {
    Route::get('google', [AuthController::class, 'redirectToGoogle'])->name('api.v1.google.redirect');
    Route::get('callback/google', [AuthController::class, 'handleGoogleCallback'])->name('api.v1.google.callback');
});

// Protected routes with authenticated rate limiter (120/min)
Route::middleware(['auth:api', 'throttle:authenticated'])->group(function (): void {

    require __DIR__ . '/admins.php';
    require __DIR__ . '/users.php';

    Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.logout');
    Route::get('me', [AuthController::class, 'me'])->name('api.v1.me');
    Route::match(['put', 'patch'], 'profile', [AuthController::class, 'updateProfile'])->name('api.v1.profile.update');
    Route::post('change-password', [AuthController::class, 'changePassword'])->name('api.v1.change-password');

    // Email verification
    Route::post('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('email/resend', [AuthController::class, 'resendVerificationEmail'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    // Password reset routes (public with rate limiting)
    Route::middleware('throttle:authenticated')->group(function (): void {
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->name('password.email');
    });

    Route::post('reset-password', [AuthController::class, 'resetPassword'])
        ->name('password.reset');
});
