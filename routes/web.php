<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\FnbMenuItemController;
use App\Http\Controllers\Admin\CreditController;
use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\ClientDetailsController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('credits')->name('credits.')->group(function () {
        Route::get('/', [CreditController::class, 'index'])->name('index');
        Route::get('/create', [CreditController::class, 'create'])->name('create');
        Route::post('/', [CreditController::class, 'store'])->name('store');
        Route::get('/{credit}/edit', [CreditController::class, 'edit'])->name('edit');
        Route::put('/{credit}', [CreditController::class, 'update'])->name('update');
        Route::delete('/{credit}', [CreditController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-destroy', [CreditController::class, 'bulkDestroy'])->name('bulk-destroy');
        
        // Admin credit request actions
        Route::post('/accept-request/{id}', [CreditController::class, 'acceptRequest'])->name('accept-request');
        Route::post('/reject-request/{id}', [CreditController::class, 'rejectRequest'])->name('reject-request');
    });

    Route::prefix('inbox')->name('inbox.')->group(function () {
        Route::get('/', [InboxController::class, 'index'])->name('index');
        Route::get('/create', [InboxController::class, 'create'])->name('create');
        Route::post('/', [InboxController::class, 'store'])->name('store');
        Route::get('/{inbox}', [InboxController::class, 'show'])->name('show');
        Route::get('/{inbox}/edit', [InboxController::class, 'edit'])->name('edit');
        Route::put('/{inbox}', [InboxController::class, 'update'])->name('update');
        Route::delete('/{inbox}', [InboxController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-destroy', [InboxController::class, 'bulkDestroy'])->name('bulk-destroy');
    });

    Route::prefix('clientdetails')->name('clientdetails.')->group(function () {
        Route::get('/', [ClientDetailsController::class, 'index'])->name('index');
        Route::get('/create', [ClientDetailsController::class, 'create'])->name('create');
        Route::post('/', [ClientDetailsController::class, 'store'])->name('store');
        Route::get('/{clientDetail}/edit', [ClientDetailsController::class, 'edit'])->name('edit');
        Route::put('/{clientDetail}', [ClientDetailsController::class, 'update'])->name('update');
        Route::delete('/{clientDetail}', [ClientDetailsController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-destroy', [ClientDetailsController::class, 'bulkDestroy'])->name('bulk-destroy');
    });

    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [ClientController::class, 'index'])->name('index');
        Route::get('/create', [ClientController::class, 'create'])->name('create');
        Route::post('/', [ClientController::class, 'store'])->name('store');
        Route::get('/{client}/edit', [ClientController::class, 'edit'])->name('edit');
        Route::put('/{client}', [ClientController::class, 'update'])->name('update');
        Route::delete('/{client}', [ClientController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-destroy', [ClientController::class, 'bulkDestroy'])->name('bulk-destroy');
    });

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
