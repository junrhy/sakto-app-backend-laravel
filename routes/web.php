<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\FnbMenuItemController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/currency', [ProfileController::class, 'updateCurrency'])->name('profile.currency');
    Route::patch('/profile/theme', [ProfileController::class, 'updateTheme'])->name('profile.theme');
    Route::patch('/profile/color', [ProfileController::class, 'updateColor'])->name('profile.color');
    Route::post('/profile/addresses', [ProfileController::class, 'updateAddresses'])->name('profile.addresses.update');
});

// Route to serve image from storage
Route::get('/image/fnb-menu-item/{filename}', [FnbMenuItemController::class, 'getImage']);

require __DIR__.'/auth.php';
