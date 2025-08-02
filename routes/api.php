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
use App\Http\Controllers\Api\LoanCbuController;
use App\Http\Controllers\Api\RentalPropertyController;
use App\Http\Controllers\Api\RentalItemController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientBillController;
use App\Http\Controllers\Api\PatientPaymentController;
use App\Http\Controllers\Api\PatientCheckupController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ContactWalletController;
use App\Http\Controllers\Api\CreditController;
use App\Http\Controllers\Api\FamilyTreeController;
use App\Http\Controllers\Api\InboxController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\FnbSettingsController;
use App\Http\Controllers\Api\FnbRestaurantController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\FoodDeliveryOrderController;
use App\Http\Controllers\Api\ChallengeController;
use App\Http\Controllers\Api\PagesController;
use App\Http\Controllers\Api\HealthInsuranceController;
use App\Http\Controllers\Api\ContentCreatorController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\ProductOrderController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\ProductReviewController;
use App\Http\Controllers\Api\MortuaryController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/restaurants', [FnbRestaurantController::class, 'index']);

    Route::get('/products-overview', [InventoryController::class, 'getProductsOverview']);
    Route::get('/tables-overview', [FnbTableController::class, 'getTablesOverview']);
    Route::get('/kitchen-orders-overview', [FnbOrderController::class, 'getKitchenOrdersOverview']);
    Route::get('/reservations-overview', [FnbReservationController::class, 'getReservationsOverview']);
 
    // Products Routes
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::patch('/{id}/stock', [ProductController::class, 'updateStock']);
        Route::get('/{id}/download', [ProductController::class, 'download']);
        Route::get('/categories', [ProductController::class, 'getCategories']);
        Route::get('/settings', [ProductController::class, 'getSettings']);
        Route::post('/bulk-delete', [ProductController::class, 'bulkDestroy']);
    });

    // Product Variants Routes
    Route::prefix('products/{productId}/variants')->group(function () {
        Route::get('/', [ProductVariantController::class, 'index']);
        Route::post('/', [ProductVariantController::class, 'store']);
        Route::get('/attributes', [ProductVariantController::class, 'getAttributes']);
        Route::post('/bulk-update', [ProductVariantController::class, 'bulkUpdate']);
        Route::get('/{variantId}', [ProductVariantController::class, 'show']);
        Route::put('/{variantId}', [ProductVariantController::class, 'update']);
        Route::delete('/{variantId}', [ProductVariantController::class, 'destroy']);
        Route::patch('/{variantId}/stock', [ProductVariantController::class, 'updateStock']);
    });

    // Product Images Routes
    Route::prefix('products/{productId}/images')->group(function () {
        Route::get('/', [ProductImageController::class, 'index']);
        Route::post('/', [ProductImageController::class, 'store']);
        Route::put('/{imageId}', [ProductImageController::class, 'update']);
        Route::delete('/{imageId}', [ProductImageController::class, 'destroy']);
        Route::post('/reorder', [ProductImageController::class, 'reorder']);
    });

    // Product Reviews Routes
    Route::prefix('products/{productId}/reviews')->group(function () {
        Route::get('/', [ProductReviewController::class, 'index']);
        Route::post('/', [ProductReviewController::class, 'store']);
        Route::get('/statistics', [ProductReviewController::class, 'statistics']);
        Route::get('/{reviewId}', [ProductReviewController::class, 'show']);
        Route::put('/{reviewId}', [ProductReviewController::class, 'update']);
        Route::delete('/{reviewId}', [ProductReviewController::class, 'destroy']);
        Route::post('/{reviewId}/vote', [ProductReviewController::class, 'vote']);
        Route::post('/{reviewId}/approve', [ProductReviewController::class, 'approve']);
        Route::post('/{reviewId}/toggle-feature', [ProductReviewController::class, 'toggleFeature']);
        Route::post('/{reviewId}/report', [ProductReviewController::class, 'report']);
    });

    // Product Review Reports Routes
    Route::prefix('product-review-reports')->group(function () {
        Route::get('/reports', [ProductReviewController::class, 'getReports']);
        Route::patch('/reports/{reportId}/status', [ProductReviewController::class, 'updateReportStatus']);
    });

    // Product Orders Routes
    Route::prefix('product-orders')->group(function () {
        Route::get('/', [ProductOrderController::class, 'index']);
        Route::post('/', [ProductOrderController::class, 'store']);
        Route::get('/statistics', [ProductOrderController::class, 'getStatistics']);
        Route::get('/recent', [ProductOrderController::class, 'getRecentOrders']);
        Route::get('/{id}', [ProductOrderController::class, 'show']);
        Route::put('/{id}', [ProductOrderController::class, 'update']);
        Route::delete('/{id}', [ProductOrderController::class, 'destroy']);
        Route::post('/{id}/process-payment', [ProductOrderController::class, 'processPayment']);
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

    // F & B Settings Routes
    Route::prefix('fnb-settings')->group(function () {
        Route::get('/', [FnbSettingsController::class, 'index']);
        Route::post('/', [FnbSettingsController::class, 'store']);
    });

    // Loan Routes
    Route::prefix('lending')->group(function () {
        Route::get('/', [LoanController::class, 'index']);
        Route::post('/', [LoanController::class, 'store']);
        Route::put('/{id}', [LoanController::class, 'update']);
        Route::delete('/{id}', [LoanController::class, 'destroy']);
        Route::post('/bulk-destroy', [LoanController::class, 'bulkDestroy']);

        Route::get('/cbu', [LoanCbuController::class, 'getCbuFunds']);
        Route::post('/cbu', [LoanCbuController::class, 'storeCbuFund']);
        Route::put('/cbu/{id}', [LoanCbuController::class, 'updateCbuFund']);
        Route::delete('/cbu/{id}', [LoanCbuController::class, 'destroyCbuFund']);
        Route::post('/cbu/{id}/contributions', [LoanCbuController::class, 'addCbuContribution']);
        Route::get('/cbu/{id}/contributions', [LoanCbuController::class, 'getCbuContributions']);
        Route::get('/cbu/{id}/dividends', [LoanCbuController::class, 'getCbuDividends']);
        Route::post('/cbu/{id}/dividend', [LoanCbuController::class, 'addCbuDividend']);
        Route::get('/cbu/{id}/withdrawals', [LoanCbuController::class, 'getCbuWithdrawals']);
        Route::post('/cbu/{id}/withdraw', [LoanCbuController::class, 'withdrawCbuFund']);
        Route::post('/cbu/{id}/process-withdrawal', [LoanCbuController::class, 'processCbuWithdrawal']);
        Route::get('/cbu/{id}/history', [LoanCbuController::class, 'getCbuHistory']);
        Route::get('/cbu/report', [LoanCbuController::class, 'generateCbuReport']);
        Route::post('/cbu/{id}/send-report', [LoanCbuController::class, 'sendFundReportEmail']);
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
        Route::post('/bulk-delete', [ContactController::class, 'bulkDestroy']);
        Route::get('/total/count', [ContactController::class, 'getTotalCount']);
    });

    // Contact Wallet Routes
    Route::prefix('contact-wallets')->group(function () {
        Route::get('/{contactId}/balance', [ContactWalletController::class, 'getBalance']);
        Route::post('/{contactId}/add-funds', [ContactWalletController::class, 'addFunds']);
        Route::post('/{contactId}/deduct-funds', [ContactWalletController::class, 'deductFunds']);
        Route::get('/{contactId}/transactions', [ContactWalletController::class, 'getTransactionHistory']);
        Route::get('/client-summary', [ContactWalletController::class, 'getClientWallets']);
        Route::post('/transfer', [ContactWalletController::class, 'transferFunds']);
    });

    // Credit Routes
    Route::prefix('credits')->group(function () {
        Route::get('/{clientIdentifier}/balance', [CreditController::class, 'getBalance']);
        Route::post('/request', [CreditController::class, 'requestCredit']);
        Route::post('/{id}/approve', [CreditController::class, 'approveCredit']);
        Route::post('/{id}/reject', [CreditController::class, 'rejectCredit']);
        Route::get('/{clientIdentifier}/history', [CreditController::class, 'getCreditHistory']);
        Route::get('/{clientIdentifier}/spent-history', [CreditController::class, 'getSpentCreditHistory']);
        Route::post('/spend', [CreditController::class, 'spendCredit']);
        Route::post('/add', [CreditController::class, 'addCredits']);
    });

    // Family Tree Routes
    Route::prefix('family-tree')->group(function () {
        Route::get('/members', [FamilyTreeController::class, 'index']);
        Route::post('/members', [FamilyTreeController::class, 'store']);
        Route::get('/members/{id}', [FamilyTreeController::class, 'show']);
        Route::put('/members/{id}', [FamilyTreeController::class, 'update']);
        Route::delete('/members/{id}', [FamilyTreeController::class, 'destroy']);
        
        // Relationship routes
        Route::post('/relationships', [FamilyTreeController::class, 'addRelationship']);
        Route::delete('/relationships/{id}', [FamilyTreeController::class, 'removeRelationship']);
        
        // Import/Export routes
        Route::get('/export', [FamilyTreeController::class, 'export']);
        Route::post('/import', [FamilyTreeController::class, 'import']);
        
        // Visualization route
        Route::get('/visualization', [FamilyTreeController::class, 'getVisualizationData']);

        // Settings routes
        Route::get('/settings', [FamilyTreeController::class, 'settings']);
        Route::post('/settings', [FamilyTreeController::class, 'saveSettings']);

        // Edit Requests
        Route::post('/edit-requests', [FamilyTreeController::class, 'editRequests']);
        Route::get('/edit-requests', [FamilyTreeController::class, 'getEditRequests']);
        Route::post('/edit-requests/{id}/accept', [FamilyTreeController::class, 'acceptEditRequest']);
        Route::post('/edit-requests/{id}/reject', [FamilyTreeController::class, 'rejectEditRequest']);
    });

    // Inbox Routes
    Route::prefix('inbox')->group(function () {
        Route::get('/', [InboxController::class, 'index']);
        Route::patch('/{id}/read', [InboxController::class, 'markAsRead']);
        Route::delete('/{id}', [InboxController::class, 'delete']);
    });

    // Client Routes
    Route::prefix('clients')->group(function () {
        Route::get('/', [ClientController::class, 'index']);
        Route::post('/', [ClientController::class, 'store']);
        Route::get('/{id}', [ClientController::class, 'show']);
        Route::put('/{id}', [ClientController::class, 'update']);
    });

    // Event Routes
    Route::prefix('events')->group(function () {
        Route::get('/', [EventController::class, 'index']);
        Route::post('/', [EventController::class, 'store']);
        Route::get('/{id}', [EventController::class, 'show']);
        Route::put('/{id}', [EventController::class, 'update']);
        Route::delete('/{id}', [EventController::class, 'destroy']);
        Route::get('/upcoming', [EventController::class, 'getUpcomingEvents']);
        Route::get('/past', [EventController::class, 'getPastEvents']);
        Route::get('/export', [EventController::class, 'exportEvents']);
        Route::get('/{id}/participants', [EventController::class, 'getParticipants']);
        Route::post('/{id}/participants/{participantId}/check-in', [EventController::class, 'checkInParticipant']);
        Route::post('/{id}/participants', [EventController::class, 'registerParticipant']);
        Route::delete('/{id}/participants/{participantId}', [EventController::class, 'unregisterParticipant']);
        Route::put('/{id}/participants/{participantId}/payment', [EventController::class, 'updatePaymentStatus']);
        Route::post('/bulk-delete', [EventController::class, 'bulkDestroy']);
    });

    // Food Delivery Order Routes
    Route::prefix('food-delivery-orders')->group(function () {
        Route::get('/', [FoodDeliveryOrderController::class, 'index']);
        Route::post('/', [FoodDeliveryOrderController::class, 'store']);
        Route::get('/{id}', [FoodDeliveryOrderController::class, 'show']);
        Route::put('/{id}', [FoodDeliveryOrderController::class, 'update']);
        Route::delete('/{id}', [FoodDeliveryOrderController::class, 'destroy']);
    });

    // Challenge Routes
    Route::prefix('challenges')->group(function () {
        Route::get('/', [ChallengeController::class, 'index']);
        Route::post('/', [ChallengeController::class, 'store']);
        Route::get('/participants-list', [ChallengeController::class, 'getParticipantsList']);
        Route::get('/{id}', [ChallengeController::class, 'show']);
        Route::put('/{id}', [ChallengeController::class, 'update']);
        Route::delete('/{id}', [ChallengeController::class, 'destroy']);
        Route::post('/bulk-delete', [ChallengeController::class, 'bulkDestroy']);
        Route::get('/{id}/participants', [ChallengeController::class, 'getParticipants']);
        Route::post('/{id}/progress', [ChallengeController::class, 'updateProgress']);
        Route::post('/{id}/participation', [ChallengeController::class, 'updateParticipationStatus']);
        Route::get('/{id}/leaderboard', [ChallengeController::class, 'getLeaderboard']);
        Route::get('/{id}/statistics', [ChallengeController::class, 'getStatistics']);
        Route::post('/{id}/participants', [ChallengeController::class, 'addParticipant']);
        Route::delete('/{id}/participants/{participantId}', [ChallengeController::class, 'removeParticipant']);
        
        // Timer routes
        Route::post('/{id}/timer/start', [ChallengeController::class, 'startTimer']);
        Route::post('/{id}/timer/stop', [ChallengeController::class, 'stopTimer']);
        Route::post('/{id}/timer/pause', [ChallengeController::class, 'pauseTimer']);
        Route::post('/{id}/timer/resume', [ChallengeController::class, 'resumeTimer']);
        Route::post('/{id}/timer/reset', [ChallengeController::class, 'resetTimer']);
        Route::get('/{id}/timer/{participantId}/status', [ChallengeController::class, 'getTimerStatus']);
    });

    // Pages Routes
    Route::prefix('pages')->group(function () {
        Route::get('/', [PagesController::class, 'index']);
        Route::get('/list', [PagesController::class, 'getPages']);
        Route::post('/', [PagesController::class, 'store']);
        Route::get('/{id}', [PagesController::class, 'show']);
        Route::put('/{id}', [PagesController::class, 'update']);
        Route::delete('/{id}', [PagesController::class, 'destroy']);
        Route::get('/slug/{slug}', [PagesController::class, 'getPage']);
        Route::get('/settings', [PagesController::class, 'settings']);
    });

    // Health Insurance Routes
    Route::prefix('health-insurance')->group(function () {
        Route::get('/', [HealthInsuranceController::class, 'index']);
        
        // Member routes
        Route::get('/members/{id}', [HealthInsuranceController::class, 'showMember']);
        Route::post('/members', [HealthInsuranceController::class, 'storeMember']);
        Route::put('/members/{id}', [HealthInsuranceController::class, 'updateMember']);
        Route::delete('/members/{id}', [HealthInsuranceController::class, 'deleteMember']);
        
        // Contribution routes
        Route::post('/contributions/{memberId}', [HealthInsuranceController::class, 'recordContribution']);
        Route::put('/contributions/{memberId}/{contributionId}', [HealthInsuranceController::class, 'updateContribution']);
        Route::get('/contributions/{memberId}', [HealthInsuranceController::class, 'getMemberContributions']);
        Route::delete('/contributions/{memberId}/{contributionId}', [HealthInsuranceController::class, 'deleteContribution']);
        
        // Claim routes
        Route::post('/claims/{memberId}', [HealthInsuranceController::class, 'submitClaim']);
        Route::put('/claims/{memberId}/{claimId}', [HealthInsuranceController::class, 'updateClaim']);
        Route::patch('/claims/{claimId}/status', [HealthInsuranceController::class, 'updateClaimStatus']);
        Route::patch('/claims/{claimId}/active-status', [HealthInsuranceController::class, 'toggleActiveStatus']);
        Route::get('/claims/{memberId}', [HealthInsuranceController::class, 'getMemberClaims']);
        Route::delete('/claims/{memberId}/{claimId}', [HealthInsuranceController::class, 'deleteClaim']);
        
        // Report routes
        Route::post('/reports', [HealthInsuranceController::class, 'generateReport']);
    });

    // Mortuary Routes
    Route::prefix('mortuary')->group(function () {
        Route::get('/', [MortuaryController::class, 'index']);
        
        // Member routes
        Route::get('/members/{id}', [MortuaryController::class, 'showMember']);
        Route::post('/members', [MortuaryController::class, 'storeMember']);
        Route::put('/members/{id}', [MortuaryController::class, 'updateMember']);
        Route::delete('/members/{id}', [MortuaryController::class, 'deleteMember']);
        
        // Contribution routes
        Route::post('/contributions/{memberId}', [MortuaryController::class, 'recordContribution']);
        Route::put('/contributions/{memberId}/{contributionId}', [MortuaryController::class, 'updateContribution']);
        Route::get('/contributions/{memberId}', [MortuaryController::class, 'getMemberContributions']);
        Route::delete('/contributions/{memberId}/{contributionId}', [MortuaryController::class, 'deleteContribution']);
        
        // Claim routes
        Route::post('/claims/{memberId}', [MortuaryController::class, 'submitClaim']);
        Route::put('/claims/{memberId}/{claimId}', [MortuaryController::class, 'updateClaim']);
        Route::patch('/claims/{claimId}/status', [MortuaryController::class, 'updateClaimStatus']);
        Route::patch('/claims/{claimId}/active-status', [MortuaryController::class, 'toggleActiveStatus']);
        Route::get('/claims/{memberId}', [MortuaryController::class, 'getMemberClaims']);
        Route::delete('/claims/{memberId}/{claimId}', [MortuaryController::class, 'deleteClaim']);
        
        // Report routes
        Route::post('/reports', [MortuaryController::class, 'generateReport']);
    });

    // Content Creator Routes
    Route::prefix('content-creator')->group(function () {
        Route::get('/', [ContentCreatorController::class, 'index']);
        Route::post('/', [ContentCreatorController::class, 'store']);
        Route::get('/{id}', [ContentCreatorController::class, 'show']);
        Route::put('/{id}', [ContentCreatorController::class, 'update']);
        Route::delete('/{id}', [ContentCreatorController::class, 'destroy']);
        Route::patch('/{id}/status', [ContentCreatorController::class, 'updateStatus']);
        Route::post('/bulk-delete', [ContentCreatorController::class, 'bulkDestroy']);
        Route::get('/{id}/preview', [ContentCreatorController::class, 'preview']);
        Route::get('/settings', [ContentCreatorController::class, 'settings']);
        Route::get('/list', [ContentCreatorController::class, 'getContent']);
        Route::get('/public/{slug}', [ContentCreatorController::class, 'publicShow']);
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