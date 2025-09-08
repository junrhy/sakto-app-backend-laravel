<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UserDataController extends Controller
{
    /**
     * Delete all user data by client_identifier
     */
    public function deleteAllUserData(Request $request): JsonResponse
    {
        $request->validate([
            'client_identifier' => 'required|string|max:255'
        ]);

        $clientIdentifier = $request->input('client_identifier');

        try {
            DB::beginTransaction();

            $deletedCounts = [];

            // Define all models that use client_identifier
            $modelsWithClientIdentifier = [
                // Core models
                'Page' => \App\Models\Page::class,
                'Product' => \App\Models\Product::class,
                'ProductOrder' => \App\Models\ProductOrder::class,
                'Inventory' => \App\Models\Inventory::class,
                'Contact' => \App\Models\Contact::class,
                'Event' => \App\Models\Event::class,
                'Challenge' => \App\Models\Challenge::class,
                'Content' => \App\Models\Content::class,
                'BillPayment' => \App\Models\BillPayment::class,
                'Biller' => \App\Models\Biller::class,
                'Course' => \App\Models\Course::class,
                'CourseEnrollment' => \App\Models\CourseEnrollment::class,
                'TransportationFleet' => \App\Models\TransportationFleet::class,
                'TransportationShipmentTracking' => \App\Models\TransportationShipmentTracking::class,
                'TransportationCargoMonitoring' => \App\Models\TransportationCargoMonitoring::class,
                'TransportationBooking' => \App\Models\TransportationBooking::class,
                'TransportationPricingConfig' => \App\Models\TransportationPricingConfig::class,
                
                // F&B models
                'FnbMenuItem' => \App\Models\FnbMenuItem::class,
                'FnbTable' => \App\Models\FnbTable::class,
                'FnbOrder' => \App\Models\FnbOrder::class,
                'FnbReservation' => \App\Models\FnbReservation::class,
                'FnbKitchenOrder' => \App\Models\FnbKitchenOrder::class,
                'FnbSale' => \App\Models\FnbSale::class,
                
                // Lending models
                'Loan' => \App\Models\Loan::class,
                'CbuFund' => \App\Models\CbuFund::class,
                'LoanPayment' => \App\Models\LoanPayment::class,
                'LoanBill' => \App\Models\LoanBill::class,
                
                // Rental models
                'RentalProperty' => \App\Models\RentalProperty::class,
                'RentalItem' => \App\Models\RentalItem::class,
                
                // Payroll
                'Payroll' => \App\Models\Payroll::class,
                
                // Patient models
                'Patient' => \App\Models\Patient::class,
                'PatientBill' => \App\Models\PatientBill::class,
                'PatientPayment' => \App\Models\PatientPayment::class,
                'PatientCheckup' => \App\Models\PatientCheckup::class,
                'PatientDentalChart' => \App\Models\PatientDentalChart::class,
                
                // Family Tree models
                'FamilyMember' => \App\Models\FamilyMember::class,
                'FamilyMemberEditRequest' => \App\Models\FamilyMemberEditRequest::class,
                'FamilyRelationship' => \App\Models\FamilyRelationship::class,
                
                // Health Insurance models
                'HealthInsuranceMember' => \App\Models\HealthInsuranceMember::class,
                'HealthInsuranceContribution' => \App\Models\HealthInsuranceContribution::class,
                'HealthInsuranceClaim' => \App\Models\HealthInsuranceClaim::class,
                
                // Mortuary models
                'MortuaryMember' => \App\Models\MortuaryMember::class,
                'MortuaryContribution' => \App\Models\MortuaryContribution::class,
                'MortuaryClaim' => \App\Models\MortuaryClaim::class,
                
                // Other models
                'FoodDeliveryOrder' => \App\Models\FoodDeliveryOrder::class,
                'Inbox' => \App\Models\Inbox::class,
                'Help' => \App\Models\Help::class,
            ];

            // Delete from models that use client_identifier
            foreach ($modelsWithClientIdentifier as $modelName => $modelClass) {
                try {
                    $count = $modelClass::where('client_identifier', $clientIdentifier)->count();
                    if ($count > 0) {
                        $modelClass::where('client_identifier', $clientIdentifier)->delete();
                        $deletedCounts[$modelName] = $count;
                        Log::info("Deleted {$count} records from {$modelName} for client {$clientIdentifier}");
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to delete from {$modelName} for client {$clientIdentifier}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Continue with other models even if one fails
                }
            }

            // Delete related models that don't have client_identifier but are related to deleted records
            $this->deleteRelatedData($clientIdentifier, $deletedCounts);

            DB::commit();

            Log::info('Successfully deleted all user data', [
                'client_identifier' => $clientIdentifier,
                'deleted_counts' => $deletedCounts
            ]);

            return response()->json([
                'success' => true,
                'message' => 'All user data deleted successfully',
                'client_identifier' => $clientIdentifier,
                'deleted_counts' => $deletedCounts
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete user data', [
                'client_identifier' => $clientIdentifier,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete related data that doesn't have client_identifier but is related to deleted records
     */
    private function deleteRelatedData(string $clientIdentifier, array &$deletedCounts): void
    {
        try {
            // Delete product variants (related to products)
            if (isset($deletedCounts['Product'])) {
                $variantCount = \App\Models\ProductVariant::whereHas('product', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($variantCount > 0) {
                    \App\Models\ProductVariant::whereHas('product', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['ProductVariant'] = $variantCount;
                }
            }

            // Delete product images (related to products)
            if (isset($deletedCounts['Product'])) {
                $imageCount = \App\Models\ProductImage::whereHas('product', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($imageCount > 0) {
                    \App\Models\ProductImage::whereHas('product', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['ProductImage'] = $imageCount;
                }
            }

            // Delete product reviews (related to products)
            if (isset($deletedCounts['Product'])) {
                $reviewCount = \App\Models\ProductReview::whereHas('product', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($reviewCount > 0) {
                    \App\Models\ProductReview::whereHas('product', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['ProductReview'] = $reviewCount;
                }
            }

            // Delete contact wallets (related to contacts)
            if (isset($deletedCounts['Contact'])) {
                $walletCount = \App\Models\ContactWallet::whereHas('contact', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($walletCount > 0) {
                    \App\Models\ContactWallet::whereHas('contact', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['ContactWallet'] = $walletCount;
                }
            }

            // Delete contact wallet transactions (related to contact wallets)
            if (isset($deletedCounts['ContactWallet'])) {
                $transactionCount = \App\Models\ContactWalletTransaction::whereHas('wallet.contact', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($transactionCount > 0) {
                    \App\Models\ContactWalletTransaction::whereHas('wallet.contact', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['ContactWalletTransaction'] = $transactionCount;
                }
            }

            // Delete event participants (related to events)
            if (isset($deletedCounts['Event'])) {
                $participantCount = \App\Models\EventParticipant::whereHas('event', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($participantCount > 0) {
                    \App\Models\EventParticipant::whereHas('event', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['EventParticipant'] = $participantCount;
                }
            }

            // Delete challenge participants (related to challenges)
            if (isset($deletedCounts['Challenge'])) {
                $participantCount = \App\Models\ChallengeParticipant::whereHas('challenge', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($participantCount > 0) {
                    \App\Models\ChallengeParticipant::whereHas('challenge', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['ChallengeParticipant'] = $participantCount;
                }
            }

            // Delete lessons (related to courses)
            if (isset($deletedCounts['Course'])) {
                $lessonCount = \App\Models\Lesson::whereHas('course', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($lessonCount > 0) {
                    \App\Models\Lesson::whereHas('course', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['Lesson'] = $lessonCount;
                }
            }

            // Delete lesson progress (related to course enrollments)
            if (isset($deletedCounts['CourseEnrollment'])) {
                $progressCount = \App\Models\LessonProgress::whereHas('enrollment', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($progressCount > 0) {
                    \App\Models\LessonProgress::whereHas('enrollment', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['LessonProgress'] = $progressCount;
                }
            }

            // Delete transportation fuel updates (related to transportation fleet)
            if (isset($deletedCounts['TransportationFleet'])) {
                $fuelCount = \App\Models\TransportationFuelUpdate::whereHas('fleet', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($fuelCount > 0) {
                    \App\Models\TransportationFuelUpdate::whereHas('fleet', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['TransportationFuelUpdate'] = $fuelCount;
                }
            }

            // Delete transportation maintenance records (related to transportation fleet)
            if (isset($deletedCounts['TransportationFleet'])) {
                $maintenanceCount = \App\Models\TransportationMaintenanceRecord::whereHas('fleet', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($maintenanceCount > 0) {
                    \App\Models\TransportationMaintenanceRecord::whereHas('fleet', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['TransportationMaintenanceRecord'] = $maintenanceCount;
                }
            }

            // Delete transportation tracking updates (related to transportation shipments)
            if (isset($deletedCounts['TransportationShipmentTracking'])) {
                $trackingCount = \App\Models\TransportationTrackingUpdate::whereHas('shipment', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($trackingCount > 0) {
                    \App\Models\TransportationTrackingUpdate::whereHas('shipment', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['TransportationTrackingUpdate'] = $trackingCount;
                }
            }

            // Delete CBU contributions (related to CBU funds)
            if (isset($deletedCounts['CbuFund'])) {
                $contributionCount = \App\Models\CbuContribution::whereHas('cbuFund', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($contributionCount > 0) {
                    \App\Models\CbuContribution::whereHas('cbuFund', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['CbuContribution'] = $contributionCount;
                }
            }

            // Delete CBU dividends (related to CBU funds)
            if (isset($deletedCounts['CbuFund'])) {
                $dividendCount = \App\Models\CbuDividend::whereHas('cbuFund', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($dividendCount > 0) {
                    \App\Models\CbuDividend::whereHas('cbuFund', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['CbuDividend'] = $dividendCount;
                }
            }

            // Delete CBU history (related to CBU funds)
            if (isset($deletedCounts['CbuFund'])) {
                $historyCount = \App\Models\CbuHistory::whereHas('cbuFund', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($historyCount > 0) {
                    \App\Models\CbuHistory::whereHas('cbuFund', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['CbuHistory'] = $historyCount;
                }
            }

            // Delete rental property payments (related to rental properties)
            if (isset($deletedCounts['RentalProperty'])) {
                $paymentCount = \App\Models\RentalPropertyPayment::whereHas('rentalProperty', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($paymentCount > 0) {
                    \App\Models\RentalPropertyPayment::whereHas('rentalProperty', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['RentalPropertyPayment'] = $paymentCount;
                }
            }

            // Delete product review reports (related to product reviews)
            if (isset($deletedCounts['ProductReview'])) {
                $reportCount = \App\Models\ProductReviewReport::whereHas('review.product', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($reportCount > 0) {
                    \App\Models\ProductReviewReport::whereHas('review.product', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['ProductReviewReport'] = $reportCount;
                }
            }

            // Delete product purchase records (related to products)
            if (isset($deletedCounts['Product'])) {
                $purchaseCount = \App\Models\ProductPurchaseRecord::whereHas('product', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($purchaseCount > 0) {
                    \App\Models\ProductPurchaseRecord::whereHas('product', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['ProductPurchaseRecord'] = $purchaseCount;
                }
            }

            // Delete product suppliers (related to products)
            if (isset($deletedCounts['Product'])) {
                $supplierCount = \App\Models\ProductSupplier::whereHas('product', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($supplierCount > 0) {
                    \App\Models\ProductSupplier::whereHas('product', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['ProductSupplier'] = $supplierCount;
                }
            }

            // Delete biller favorites (related to billers)
            if (isset($deletedCounts['Biller'])) {
                $favoriteCount = \App\Models\BillerFavorite::whereHas('biller', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($favoriteCount > 0) {
                    \App\Models\BillerFavorite::whereHas('biller', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['BillerFavorite'] = $favoriteCount;
                }
            }

            // Delete credits (related to client)
            $creditCount = \App\Models\Credit::where('client_identifier', $clientIdentifier)->count();
            if ($creditCount > 0) {
                \App\Models\Credit::where('client_identifier', $clientIdentifier)->delete();
                $deletedCounts['Credit'] = $creditCount;
            }

            // Delete credit history (related to credits)
            if (isset($deletedCounts['Credit'])) {
                $creditHistoryCount = \App\Models\CreditHistory::whereHas('credit', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($creditHistoryCount > 0) {
                    \App\Models\CreditHistory::whereHas('credit', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['CreditHistory'] = $creditHistoryCount;
                }
            }

            // Delete credit spent history (related to credits)
            if (isset($deletedCounts['Credit'])) {
                $creditSpentCount = \App\Models\CreditSpentHistory::whereHas('credit', function($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->count();
                if ($creditSpentCount > 0) {
                    \App\Models\CreditSpentHistory::whereHas('credit', function($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->delete();
                    $deletedCounts['CreditSpentHistory'] = $creditSpentCount;
                }
            }

            // Delete warehouse items (related to client)
            $warehouseItemCount = \App\Models\WarehouseItem::where('client_identifier', $clientIdentifier)->count();
            if ($warehouseItemCount > 0) {
                \App\Models\WarehouseItem::where('client_identifier', $clientIdentifier)->delete();
                $deletedCounts['WarehouseItem'] = $warehouseItemCount;
            }

            // Delete warehouse orders (related to client)
            $warehouseOrderCount = \App\Models\WarehouseOrder::where('client_identifier', $clientIdentifier)->count();
            if ($warehouseOrderCount > 0) {
                \App\Models\WarehouseOrder::where('client_identifier', $clientIdentifier)->delete();
                $deletedCounts['WarehouseOrder'] = $warehouseOrderCount;
            }

            // Delete warehouse receiving (related to client)
            $warehouseReceivingCount = \App\Models\WarehouseReceiving::where('client_identifier', $clientIdentifier)->count();
            if ($warehouseReceivingCount > 0) {
                \App\Models\WarehouseReceiving::where('client_identifier', $clientIdentifier)->delete();
                $deletedCounts['WarehouseReceiving'] = $warehouseReceivingCount;
            }

            // Delete retail items (related to client)
            $retailItemCount = \App\Models\RetailItem::where('client_identifier', $clientIdentifier)->count();
            if ($retailItemCount > 0) {
                \App\Models\RetailItem::where('client_identifier', $clientIdentifier)->delete();
                $deletedCounts['RetailItem'] = $retailItemCount;
            }

            // Delete retail orders (related to client)
            $retailOrderCount = \App\Models\RetailOrder::where('client_identifier', $clientIdentifier)->count();
            if ($retailOrderCount > 0) {
                \App\Models\RetailOrder::where('client_identifier', $clientIdentifier)->delete();
                $deletedCounts['RetailOrder'] = $retailOrderCount;
            }

            // Delete retail sales (related to client)
            $retailSaleCount = \App\Models\RetailSale::where('client_identifier', $clientIdentifier)->count();
            if ($retailSaleCount > 0) {
                \App\Models\RetailSale::where('client_identifier', $clientIdentifier)->delete();
                $deletedCounts['RetailSale'] = $retailSaleCount;
            }

            // Delete retail categories (related to client)
            $retailCategoryCount = \App\Models\RetailCategory::where('client_identifier', $clientIdentifier)->count();
            if ($retailCategoryCount > 0) {
                \App\Models\RetailCategory::where('client_identifier', $clientIdentifier)->delete();
                $deletedCounts['RetailCategory'] = $retailCategoryCount;
            }

            // Delete client details (related to client)
            $clientDetailsCount = \App\Models\ClientDetails::where('client_identifier', $clientIdentifier)->count();
            if ($clientDetailsCount > 0) {
                \App\Models\ClientDetails::where('client_identifier', $clientIdentifier)->delete();
                $deletedCounts['ClientDetails'] = $clientDetailsCount;
            }

        } catch (\Exception $e) {
            Log::error('Failed to delete related data', [
                'client_identifier' => $clientIdentifier,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw exception here, just log the error
        }
    }
}
