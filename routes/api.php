<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InventoryController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Inventory Routes
    Route::prefix('inventories')->group(function () {
        Route::apiResource('/', InventoryController::class);
        Route::delete('/bulk-destroy', [InventoryController::class, 'bulkDestroy']);
    });

    // Future authenticated routes will go here...
});
