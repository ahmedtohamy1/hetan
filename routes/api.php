<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\DonatorController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\ImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public routes (no authentication required)
Route::get('donators', [DonatorController::class, 'index']); // For donation page search
Route::post('donations', [DonationController::class, 'store']); // For donation confirmation
Route::get('settings/global-donation-number', [SettingsController::class, 'getGlobalDonationNumber']); // Global donation number

// Admin authentication routes
Route::prefix('admin')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login']);
    Route::post('logout', [AdminAuthController::class, 'logout']);
    Route::get('check', [AdminAuthController::class, 'check']);
});

    // Protected admin routes (require authentication)
    Route::middleware(['admin'])->prefix('admin')->group(function () {
        // Donator management (admin only)
        Route::get('donators', [DonatorController::class, 'index']);
        Route::post('donators', [DonatorController::class, 'store']);
        Route::put('donators/{donator}', [DonatorController::class, 'update']);
        Route::delete('donators/{donator}', [DonatorController::class, 'destroy']);

        // Donation management (admin only)
        Route::get('donations', [DonationController::class, 'index']);
        Route::get('donations/{donation}', [DonationController::class, 'show']);
        Route::put('donations/{donation}', [DonationController::class, 'update']);
        Route::delete('donations/{donation}', [DonationController::class, 'destroy']);


        // Settings management
        Route::get('settings/global-donation-number', [SettingsController::class, 'getGlobalDonationNumber']);
        Route::put('settings/global-donation-number', [SettingsController::class, 'updateGlobalDonationNumber']);

        // Import routes (admin only)
        Route::post('import/donators', [ImportController::class, 'importDonators']);
    });
