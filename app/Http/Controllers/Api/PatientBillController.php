<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientBill;
use App\Models\Patient;
use App\Models\ClinicPaymentAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PatientBillController extends Controller
{
    public function getBills($patientId): JsonResponse
    {
        $patientBills = PatientBill::where('patient_id', $patientId)
            ->with(['patient', 'clinicPaymentAccount'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Add billing info to each bill
        $patientBills->each(function ($bill) {
            $bill->billing_info = $bill->billing_info;
        });

        return response()->json([
            'success' => true,
            'bills' => $patientBills
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|integer|exists:patients,id',
            'bill_number' => 'nullable|string|max:255',
            'bill_date' => 'required|date',
            'bill_amount' => 'required|numeric|min:0|max:999999999.99',
            'bill_status' => 'nullable|string|max:255',
            'bill_details' => 'nullable|string|max:1000',
            'billing_type' => 'nullable|in:individual,account',
            'clinic_payment_account_id' => 'nullable|integer|exists:clinic_payment_accounts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            // Get patient to check billing type
            $patient = Patient::findOrFail($validated['patient_id']);
            
            // Determine billing type
            if (!isset($validated['billing_type'])) {
                $validated['billing_type'] = $patient->billing_type ?? PatientBill::BILLING_INDIVIDUAL;
            }

            // If account billing, set the account ID and generate reference
            if ($validated['billing_type'] === PatientBill::BILLING_ACCOUNT) {
                if ($patient->clinic_payment_account_id) {
                    $validated['clinic_payment_account_id'] = $patient->clinic_payment_account_id;
                    $account = ClinicPaymentAccount::find($patient->clinic_payment_account_id);
                    if ($account) {
                        $validated['account_bill_reference'] = PatientBill::generateAccountBillReference($account);
                    }
                } elseif (isset($validated['clinic_payment_account_id'])) {
                    // Use provided account ID
                    $account = ClinicPaymentAccount::find($validated['clinic_payment_account_id']);
                    if ($account) {
                        $validated['account_bill_reference'] = PatientBill::generateAccountBillReference($account);
                    }
                }
            }

            // Set default bill status
            if (!isset($validated['bill_status'])) {
                $validated['bill_status'] = PatientBill::STATUS_PENDING;
            }

            $patientBill = PatientBill::create($validated);
            
            // Load relationships for response
            $patientBill->load(['patient', 'clinicPaymentAccount']);
            $patientBill->billing_info = $patientBill->billing_info;

            return response()->json([
                'success' => true,
                'data' => $patientBill,
                'message' => 'Bill created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($patientId, $id): JsonResponse
    {
        try {
            $patientBill = PatientBill::where('patient_id', $patientId)->findOrFail($id);
            $patientBill->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Bill deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create account-based bill for multiple patients
     */
    public function storeAccountBill(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'clinic_payment_account_id' => 'required|integer|exists:clinic_payment_accounts,id',
            'patient_ids' => 'required|array|min:1',
            'patient_ids.*' => 'required|integer|exists:patients,id',
            'bill_date' => 'required|date',
            'bill_amount' => 'required|numeric|min:0|max:999999999.99',
            'bill_details' => 'nullable|string|max:1000',
            'distribute_amount' => 'nullable|boolean', // If true, split amount among patients
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            $account = ClinicPaymentAccount::findOrFail($validated['clinic_payment_account_id']);
            $patientIds = $validated['patient_ids'];
            $totalAmount = $validated['bill_amount'];
            $distributeAmount = $validated['distribute_amount'] ?? false;
            
            // Calculate amount per patient if distributing
            $amountPerPatient = $distributeAmount ? $totalAmount / count($patientIds) : $totalAmount;
            
            $bills = [];
            $accountBillReference = PatientBill::generateAccountBillReference($account);

            foreach ($patientIds as $patientId) {
                $billData = [
                    'patient_id' => $patientId,
                    'bill_date' => $validated['bill_date'],
                    'bill_amount' => $amountPerPatient,
                    'bill_details' => $validated['bill_details'] ?? 'Account-based bill',
                    'bill_status' => PatientBill::STATUS_PENDING,
                    'billing_type' => PatientBill::BILLING_ACCOUNT,
                    'clinic_payment_account_id' => $account->id,
                    'account_bill_reference' => $accountBillReference,
                ];

                $bill = PatientBill::create($billData);
                $bill->load(['patient', 'clinicPaymentAccount']);
                $bill->billing_info = $bill->billing_info;
                $bills[] = $bill;
            }

            return response()->json([
                'success' => true,
                'data' => $bills,
                'message' => 'Account-based bills created successfully',
                'total_amount' => $totalAmount,
                'amount_per_patient' => $amountPerPatient,
                'patients_count' => count($patientIds)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update bill status
     */
    public function updateStatus(Request $request, $billId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bill_status' => 'required|in:pending,paid,partial,overdue,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            $bill = PatientBill::findOrFail($billId);
            $bill->update(['bill_status' => $validated['bill_status']]);
            
            // Load relationships for response
            $bill->load(['patient', 'clinicPaymentAccount']);
            $bill->billing_info = $bill->billing_info;

            return response()->json([
                'success' => true,
                'data' => $bill,
                'message' => 'Bill status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
