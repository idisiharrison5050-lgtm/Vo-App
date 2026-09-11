<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/marketplace/countries', [MarketplaceController::class, 'countries']);
    Route::get('/marketplace/services', [MarketplaceController::class, 'services']);
    Route::get('/marketplace/offers', [MarketplaceController::class, 'offers']);

    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', function (\Illuminate\Http\Request $request) {
            return response()->json([
                'success' => true,
                'message' => 'OK',
                'data' => $request->user(),
            ]);
        });
    });
});
