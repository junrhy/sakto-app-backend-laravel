<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientPayment;
use App\Models\Patient;
use App\Models\ClinicPaymentAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PatientPaymentController extends Controller
{
    public function getPayments($patientId): JsonResponse
    {
        $patientPayments = PatientPayment::where('patient_id', $patientId)
            ->with(['patient', 'clinicPaymentAccount'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Add payment info to each payment
        $patientPayments->each(function ($payment) {
            $payment->payment_info = $payment->payment_info;
        });

        return response()->json([
            'success' => true,
            'payments' => $patientPayments
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|integer|exists:patients,id',
            'payment_date' => 'required|date',
            'payment_amount' => 'required|numeric|min:0|max:999999999.99',
            'payment_method' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string|max:1000',
            'payment_type' => 'nullable|in:individual,account',
            'clinic_payment_account_id' => 'nullable|integer|exists:clinic_payment_accounts,id',
            'covered_patients' => 'nullable|array',
            'covered_patients.*' => 'integer|exists:patients,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            // Get patient to check payment type
            $patient = Patient::findOrFail($validated['patient_id']);
            
            // Determine payment type
            if (!isset($validated['payment_type'])) {
                $validated['payment_type'] = $patient->billing_type === Patient::BILLING_ACCOUNT 
                    ? PatientPayment::PAYMENT_ACCOUNT 
                    : PatientPayment::PAYMENT_INDIVIDUAL;
            }

            // If account payment, set the account ID and generate reference
            if ($validated['payment_type'] === PatientPayment::PAYMENT_ACCOUNT) {
                if ($patient->clinic_payment_account_id) {
                    $validated['clinic_payment_account_id'] = $patient->clinic_payment_account_id;
                    $account = ClinicPaymentAccount::find($patient->clinic_payment_account_id);
                    if ($account) {
                        $validated['account_payment_reference'] = PatientPayment::generateAccountPaymentReference($account);
                    }
                } elseif (isset($validated['clinic_payment_account_id'])) {
                    // Use provided account ID
                    $account = ClinicPaymentAccount::find($validated['clinic_payment_account_id']);
                    if ($account) {
                        $validated['account_payment_reference'] = PatientPayment::generateAccountPaymentReference($account);
                    }
                }

                // Set covered patients (default to the payment patient if not provided)
                if (!isset($validated['covered_patients'])) {
                    $validated['covered_patients'] = [$validated['patient_id']];
                }
            }

            // Set default payment method
            if (!isset($validated['payment_method'])) {
                $validated['payment_method'] = 'cash';
            }

            $patientPayment = PatientPayment::create($validated);
            
            // Load relationships for response
            $patientPayment->load(['patient', 'clinicPaymentAccount']);
            $patientPayment->payment_info = $patientPayment->payment_info;

            return response()->json([
                'success' => true,
                'data' => $patientPayment,
                'message' => 'Payment recorded successfully'
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
            $patientPayment = PatientPayment::where('patient_id', $patientId)->findOrFail($id);
            $patientPayment->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create account-based payment covering multiple patients
     */
    public function storeAccountPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'clinic_payment_account_id' => 'required|integer|exists:clinic_payment_accounts,id',
            'patient_ids' => 'required|array|min:1',
            'patient_ids.*' => 'required|integer|exists:patients,id',
            'payment_date' => 'required|date',
            'payment_amount' => 'required|numeric|min:0|max:999999999.99',
            'payment_method' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string|max:1000',
            'primary_patient_id' => 'nullable|integer|exists:patients,id', // Patient to associate the payment with
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            $account = ClinicPaymentAccount::findOrFail($validated['clinic_payment_account_id']);
            $patientIds = $validated['patient_ids'];
            
            // Use primary patient or first patient in the list
            $primaryPatientId = $validated['primary_patient_id'] ?? $patientIds[0];
            
            $paymentData = [
                'patient_id' => $primaryPatientId,
                'payment_date' => $validated['payment_date'],
                'payment_amount' => $validated['payment_amount'],
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'payment_notes' => $validated['payment_notes'] ?? 'Account-based payment',
                'payment_type' => PatientPayment::PAYMENT_ACCOUNT,
                'clinic_payment_account_id' => $account->id,
                'account_payment_reference' => PatientPayment::generateAccountPaymentReference($account),
                'covered_patients' => $patientIds,
            ];

            $payment = PatientPayment::create($paymentData);
            $payment->load(['patient', 'clinicPaymentAccount']);
            $payment->payment_info = $payment->payment_info;

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Account-based payment recorded successfully',
                'covered_patients_count' => count($patientIds)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all payments for an account
     */
    public function getAccountPayments(Request $request, $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            $account = ClinicPaymentAccount::where('id', $accountId)
                ->where('client_identifier', $validated['client_identifier'])
                ->first();

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found'
                ], 404);
            }

            $payments = PatientPayment::where('clinic_payment_account_id', $accountId)
                ->with(['patient', 'clinicPaymentAccount'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Add payment info to each payment
            $payments->each(function ($payment) {
                $payment->payment_info = $payment->payment_info;
            });

            return response()->json([
                'success' => true,
                'data' => $payments,
                'total_amount' => $payments->sum('payment_amount')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
