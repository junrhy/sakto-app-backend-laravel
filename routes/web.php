<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\FnbMenuItemController;
use App\Http\Controllers\CreditAdminController;
use App\Http\Controllers\InboxAdminController;
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

    Route::get('/admin/credits', [CreditAdminController::class, 'index'])->name('credits.index');
    Route::post('/admin/credits/accept-request/{id}', [CreditAdminController::class, 'acceptRequest'])->name('credits.accept');
    Route::post('/admin/credits/reject-request/{id}', [CreditAdminController::class, 'rejectRequest'])->name('credits.reject');

    // Inbox Admin routes
    Route::prefix('inbox-admin')->group(function () {
        Route::get('/', [InboxAdminController::class, 'index'])->name('inbox-admin.index');
        Route::get('/create', [InboxAdminController::class, 'create'])->name('inbox-admin.create');
        Route::post('/', [InboxAdminController::class, 'store'])->name('inbox-admin.store');
        Route::get('/{id}', [InboxAdminController::class, 'show'])->name('inbox-admin.show');
        Route::get('/{id}/edit', [InboxAdminController::class, 'edit'])->name('inbox-admin.edit');
        Route::put('/{id}', [InboxAdminController::class, 'update'])->name('inbox-admin.update');
        Route::delete('/{id}', [InboxAdminController::class, 'destroy'])->name('inbox-admin.destroy');
        Route::post('/bulk-delete', [InboxAdminController::class, 'bulkDestroy'])->name('inbox-admin.bulk-destroy');
    });
});

// Route to serve image from storage
Route::get('/image/fnb-menu-item/{filename}', [FnbMenuItemController::class, 'getImage']);

require __DIR__.'/auth.php';
