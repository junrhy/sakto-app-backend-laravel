<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\RetailSaleController;
use App\Http\Controllers\Api\FnbMenuItemController;
use App\Http\Controllers\Api\FnbTableController;
use App\Http\Controllers\Api\FnbOrderController;
use App\Http\Controllers\Api\FnbReservationController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\LoanPaymentController;
use App\Http\Controllers\Api\LoanBillController;
use App\Http\Controllers\Api\RentalPropertyController;
use App\Http\Controllers\Api\RentalItemController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientBillController;
use App\Http\Controllers\Api\PatientPaymentController;
use App\Http\Controllers\Api\PatientCheckupController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CreditController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/products-overview', [InventoryController::class, 'getProductsOverview']);
    Route::get('/tables-overview', [FnbTableController::class, 'getTablesOverview']);
    Route::get('/kitchen-orders-overview', [FnbOrderController::class, 'getKitchenOrdersOverview']);
    Route::get('/reservations-overview', [FnbReservationController::class, 'getReservationsOverview']);
 
    // Inventory Routes
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::post('/', [InventoryController::class, 'store']);
        Route::get('/{id}', [InventoryController::class, 'show']);
        Route::put('/{id}', [InventoryController::class, 'update']);
        Route::delete('/{id}', [InventoryController::class, 'destroy']);
        Route::post('/bulk-destroy', [InventoryController::class, 'bulkDestroy']);
    });

    // Pos Retail Routes
    Route::prefix('pos-retail')->group(function () {
        Route::get('/sales', [RetailSaleController::class, 'index']);
        Route::post('/', [RetailSaleController::class, 'store']);
        Route::delete('/sale/{id}', [RetailSaleController::class, 'destroy']);
        Route::post('/sales/bulk-delete', [RetailSaleController::class, 'bulkDelete']);
        Route::get('/sales/overview', [RetailSaleController::class, 'getSalesOverview']);
    });

    // F & B Menu Items Routes
    Route::prefix('fnb-menu-items')->group(function () {
        Route::get('/', [FnbMenuItemController::class, 'index']);
        Route::post('/', [FnbMenuItemController::class, 'store']);
        Route::get('/{id}', [FnbMenuItemController::class, 'show']);
        Route::put('/{id}', [FnbMenuItemController::class, 'update']);
        Route::delete('/{id}', [FnbMenuItemController::class, 'destroy']);
        Route::post('/bulk-destroy', [FnbMenuItemController::class, 'bulkDestroy']);
    });

    // F & B Table Routes
    Route::prefix('fnb-tables')->group(function () {
        Route::get('/', [FnbTableController::class, 'index']);
        Route::post('/', [FnbTableController::class, 'store']);
        Route::put('/{fnbTable}', [FnbTableController::class, 'update']);
        Route::delete('/{fnbTable}', [FnbTableController::class, 'destroy']);
        Route::post('/join', [FnbTableController::class, 'joinTables']);
        Route::post('/unjoin', [FnbTableController::class, 'unjoinTables']);
        Route::get('/joined', [FnbTableController::class, 'getJoinedTables']);
    });

    // F & B Order Routes
    Route::prefix('fnb-orders')->group(function () {
        Route::get('/client/{clientIdentifier}/table/{tableNumber}', [FnbOrderController::class, 'index']);
        Route::post('/add-item', [FnbOrderController::class, 'addItemToOrder']);
        Route::delete('/{tableNumber}/item/{id}', [FnbOrderController::class, 'destroy']);
        Route::post('/complete', [FnbOrderController::class, 'completeOrder']);
        Route::post('/kitchen-order', [FnbOrderController::class, 'storeKitchenOrder']);
    });

    // F & B Reservation Routes
    Route::prefix('fnb-reservations')->group(function () {
        Route::get('/', [FnbReservationController::class, 'index']);
        Route::post('/', [FnbReservationController::class, 'store']);
        Route::delete('/{id}', [FnbReservationController::class, 'destroy']);
    });

    // Loan Routes
    Route::prefix('lending')->group(function () {
        Route::get('/', [LoanController::class, 'index']);
        Route::post('/', [LoanController::class, 'store']);
        Route::put('/{id}', [LoanController::class, 'update']);
        Route::delete('/{id}', [LoanController::class, 'destroy']);
        Route::post('/bulk-destroy', [LoanController::class, 'bulkDestroy']);
    });

    // Loan Payment Routes
    Route::prefix('loan-payments')->group(function () {
        Route::post('/{loan_id}', [LoanPaymentController::class, 'store']);
        Route::delete('/{loan_id}/{payment_id}', [LoanPaymentController::class, 'destroy']);
    });

    // Loan Bill Routes
    Route::prefix('loan-bills')->group(function () {
        Route::get('/{loan_id}', [LoanBillController::class, 'index']);
        Route::post('/{loan_id}', [LoanBillController::class, 'store']);
        Route::put('/{id}', [LoanBillController::class, 'update']);
        Route::delete('/{id}', [LoanBillController::class, 'destroy']);
        Route::put('/{id}/status', [LoanBillController::class, 'updateStatus']);
    });

    // Payroll Routes
    Route::prefix('payroll')->group(function () {
        Route::get('/', [PayrollController::class, 'index']);
        Route::post('/', [PayrollController::class, 'store']);
        Route::put('/{id}', [PayrollController::class, 'update']);
        Route::delete('/{id}', [PayrollController::class, 'destroy']);
        Route::delete('/bulk', [PayrollController::class, 'bulkDestroy']);
        Route::get('/overview', [PayrollController::class, 'getPayrollOverview']);
    });

    // Rental Property Routes
    Route::prefix('rental-property')->group(function () {
        Route::get('/', [RentalPropertyController::class, 'index']);
        Route::get('/list', [RentalPropertyController::class, 'getProperties']);
        Route::post('/', [RentalPropertyController::class, 'store']);
        Route::put('/{id}', [RentalPropertyController::class, 'update']);
        Route::delete('/{id}', [RentalPropertyController::class, 'destroy']);
        Route::post('/bulk', [RentalPropertyController::class, 'bulkDestroy']);
        Route::post('/{id}/payment', [RentalPropertyController::class, 'recordPayment']);
        Route::get('/{id}/payment-history', [RentalPropertyController::class, 'getPaymentHistory']);
    });

    // Rental Item Routes
    Route::prefix('rental-items')->group(function () {
        Route::get('/', [RentalItemController::class, 'index']);
        Route::post('/', [RentalItemController::class, 'store']);
        Route::put('/{id}', [RentalItemController::class, 'update']);
        Route::delete('/{id}', [RentalItemController::class, 'destroy']);
        Route::post('/bulk-delete', [RentalItemController::class, 'bulkDestroy']);
        Route::post('/{id}/payment', [RentalItemController::class, 'recordPayment']);
        Route::get('/{id}/payment-history', [RentalItemController::class, 'getPaymentHistory']);
    });

    // Patient Routes
    Route::prefix('patients')->group(function () {
        Route::get('/', [PatientController::class, 'index']);
        Route::post('/', [PatientController::class, 'store']);
        Route::get('/{id}', [PatientController::class, 'show']);
        Route::put('/{id}', [PatientController::class, 'update']);
        Route::delete('/{id}', [PatientController::class, 'destroy']);
        Route::put('/{id}/next-visit', [PatientController::class, 'updateNextVisit']);
    });

    // Patient Bill Routes
    Route::prefix('patient-bills')->group(function () {
        Route::post('/{id}', [PatientBillController::class, 'store']);
        Route::delete('/{patientId}/{id}', [PatientBillController::class, 'destroy']);
        Route::get('/{id}', [PatientBillController::class, 'getBills']);
    });

    // Patient Payment Routes
    Route::prefix('patient-payments')->group(function () {
        Route::post('/{id}', [PatientPaymentController::class, 'store']);
        Route::delete('/{patientId}/{id}', [PatientPaymentController::class, 'destroy']);
        Route::get('/{id}', [PatientPaymentController::class, 'getPayments']);
    });

    // Patient Checkup Routes
    Route::prefix('patient-checkups')->group(function () {
        Route::post('/{patientId}', [PatientCheckupController::class, 'store']);
        Route::delete('/{patientId}/{checkupId}', [PatientCheckupController::class, 'destroy']);
        Route::get('/{patientId}', [PatientCheckupController::class, 'getCheckups']);
    });

    // Contact Routes
    Route::prefix('contacts')->group(function () {
        Route::get('/', [ContactController::class, 'index']);
        Route::post('/', [ContactController::class, 'store']);
        Route::get('/{id}', [ContactController::class, 'show']);
        Route::put('/{id}', [ContactController::class, 'update']);
        Route::delete('/{id}', [ContactController::class, 'destroy']);
    });

    // Credit Routes
    Route::prefix('credits')->group(function () {
        Route::get('/{clientIdentifier}/balance', [CreditController::class, 'getBalance']);
        Route::post('/request', [CreditController::class, 'requestCredit']);
        Route::post('/{id}/approve', [CreditController::class, 'approveCredit']);
        Route::post('/{id}/reject', [CreditController::class, 'rejectCredit']);
        Route::get('/{clientIdentifier}/history', [CreditController::class, 'getCreditHistory']);
        Route::post('/spend', [CreditController::class, 'spendCredit']);
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