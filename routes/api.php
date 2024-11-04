<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InventoryController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Inventory Routes
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::post('/', [InventoryController::class, 'store']);
        Route::get('/{id}', [InventoryController::class, 'show']);
        Route::put('/{id}', [InventoryController::class, 'update']);
        Route::delete('/{id}', [InventoryController::class, 'destroy']);
        Route::post('/bulk-destroy', [InventoryController::class, 'bulkDestroy']);
    });

    // Future authenticated routes will go here...
});

Route::post('/tokens/create', function (Request $request) {
    $user = User::where('email', $request->email)->first();
    $password = $request->password;

    if (!$user || !Hash::check($password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    $token = $user->createToken($user->name);
 
    return ['token' => $token->plainTextToken];
});