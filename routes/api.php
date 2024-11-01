<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Inventory Routes
    Route::prefix('inventories')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::post('/', [InventoryController::class, 'store']);
        Route::get('/{inventory}', [InventoryController::class, 'show']);
        Route::put('/{inventory}', [InventoryController::class, 'update']);
        Route::delete('/{inventory}', [InventoryController::class, 'destroy']);
        Route::delete('/bulk-destroy', [InventoryController::class, 'bulkDestroy']);
    });

    // Future authenticated routes will go here...
});
