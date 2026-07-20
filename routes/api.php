<?php

declare(strict_types=1);

use App\Http\Controllers\Api\DestinationController;
use App\Http\Controllers\Api\MapDataController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/destinations', [DestinationController::class, 'index']);
    Route::post('/destinations', [DestinationController::class, 'store']);
    Route::put('/destinations/reorder', [DestinationController::class, 'reorder']);
    Route::delete('/destinations/{destination}', [DestinationController::class, 'destroy']);

    Route::prefix('map')->group(function (): void {
        Route::get('/search', [MapDataController::class, 'search']);
        Route::get('/reverse', [MapDataController::class, 'reverse']);
        Route::get('/points-of-interest', [MapDataController::class, 'pointsOfInterest']);
    });
});
