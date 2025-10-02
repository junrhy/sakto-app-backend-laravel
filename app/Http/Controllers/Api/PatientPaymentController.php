<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientPayment;
use App\Models\Patient;
use App\Models\ClinicPaymentAccount;
use App\Models\Appointment;
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

    /**
     * Get revenue statistics for dashboard widget
     */
    public function getStats(Request $request)
    {
        try {
            $clientIdentifier = $request->input('client_identifier');
            
            if (!$clientIdentifier) {
                return response()->json(['error' => 'Client identifier is required'], 400);
            }

            // Get total revenue (all time) - cast payment_amount to decimal for PostgreSQL
            $totalRevenue = PatientPayment::whereHas('patient', function($query) use ($clientIdentifier) {
                $query->where('client_identifier', $clientIdentifier);
            })->selectRaw('SUM(CAST(payment_amount AS DECIMAL(10,2))) as total')->value('total') ?? 0;
            
            // Get today's revenue
            $todayRevenue = PatientPayment::whereHas('patient', function($query) use ($clientIdentifier) {
                $query->where('client_identifier', $clientIdentifier);
            })->whereDate('payment_date', today())
              ->selectRaw('SUM(CAST(payment_amount AS DECIMAL(10,2))) as total')->value('total') ?? 0;
            
            // Get this month's revenue
            $monthlyRevenue = PatientPayment::whereHas('patient', function($query) use ($clientIdentifier) {
                $query->where('client_identifier', $clientIdentifier);
            })->whereMonth('payment_date', now()->month)
              ->whereYear('payment_date', now()->year)
              ->selectRaw('SUM(CAST(payment_amount AS DECIMAL(10,2))) as total')->value('total') ?? 0;
            
            // Get last month's revenue for growth calculation
            $lastMonthRevenue = PatientPayment::whereHas('patient', function($query) use ($clientIdentifier) {
                $query->where('client_identifier', $clientIdentifier);
            })->whereMonth('payment_date', now()->subMonth()->month)
              ->whereYear('payment_date', now()->subMonth()->year)
              ->selectRaw('SUM(CAST(payment_amount AS DECIMAL(10,2))) as total')->value('total') ?? 0;
            
            // Calculate revenue growth percentage
            $revenueGrowth = 0;
            if ($lastMonthRevenue > 0) {
                $revenueGrowth = (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
            } elseif ($monthlyRevenue > 0) {
                $revenueGrowth = 100; // 100% growth if no previous revenue
            }
            
            // Get outstanding amount (from appointments with pending payments) - cast fee to decimal
            $outstandingAmount = \App\Models\Appointment::where('client_identifier', $clientIdentifier)
                ->where('payment_status', 'pending')
                ->selectRaw('SUM(CAST(fee AS DECIMAL(10,2))) as total')->value('total') ?? 0;
            
            // Get payment methods breakdown - cast payment_amount to decimal
            $paymentMethods = PatientPayment::whereHas('patient', function($query) use ($clientIdentifier) {
                $query->where('client_identifier', $clientIdentifier);
            })->whereMonth('payment_date', now()->month)
              ->whereYear('payment_date', now()->year)
              ->selectRaw('payment_method, SUM(CAST(payment_amount AS DECIMAL(10,2))) as total')
              ->groupBy('payment_method')
              ->pluck('total', 'payment_method')
              ->toArray();
            
            // Ensure all payment methods are represented
            $defaultPaymentMethods = [
                'cash' => 0,
                'card' => 0,
                'insurance' => 0,
                'online' => 0,
                'other' => 0
            ];
            
            $paymentMethods = array_merge($defaultPaymentMethods, $paymentMethods);
            
            return response()->json([
                'total_revenue' => $totalRevenue,
                'today_revenue' => $todayRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'outstanding_amount' => $outstandingAmount,
                'revenue_growth' => round($revenueGrowth, 2),
                'payment_methods' => $paymentMethods
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to fetch revenue statistics', [
                'error' => $e->getMessage(),
                'client_identifier' => $request->input('client_identifier')
            ]);
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get payment statistics for dashboard widget
     */
    public function getPaymentStats(Request $request)
    {
        try {
            $clientIdentifier = $request->input('client_identifier');
            
            if (!$clientIdentifier) {
                return response()->json(['error' => 'Client identifier is required'], 400);
            }

            // Get total payments count
            $totalPayments = PatientPayment::whereHas('patient', function($query) use ($clientIdentifier) {
                $query->where('client_identifier', $clientIdentifier);
            })->count();
            
            // Get pending payments (from appointments with pending payment status)
            $pendingPayments = \App\Models\Appointment::where('client_identifier', $clientIdentifier)
                ->where('payment_status', 'pending')
                ->count();
            
            // Get overdue payments (appointments with pending status that are past due)
            $overduePayments = \App\Models\Appointment::where('client_identifier', $clientIdentifier)
                ->where('payment_status', 'pending')
                ->where('appointment_date', '<', today())
                ->count();
            
            // Get payment methods breakdown for current month
            $paymentMethods = PatientPayment::whereHas('patient', function($query) use ($clientIdentifier) {
                $query->where('client_identifier', $clientIdentifier);
            })->whereMonth('payment_date', now()->month)
              ->whereYear('payment_date', now()->year)
              ->selectRaw('payment_method, COUNT(*) as count')
              ->groupBy('payment_method')
              ->pluck('count', 'payment_method')
              ->toArray();
            
            // Ensure all payment methods are represented
            $defaultPaymentMethods = [
                'cash' => 0,
                'card' => 0,
                'insurance' => 0,
                'online' => 0,
                'other' => 0
            ];
            
            $paymentMethods = array_merge($defaultPaymentMethods, $paymentMethods);
            
            // Calculate average payment time (simplified - just return a default value for now)
            $averagePaymentTime = 2.5; // Default value
            
            return response()->json([
                'total_payments' => $totalPayments,
                'pending_payments' => $pendingPayments,
                'overdue_payments' => $overduePayments,
                'payment_methods' => $paymentMethods,
                'average_payment_time' => $averagePaymentTime
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to fetch payment statistics', [
                'error' => $e->getMessage(),
                'client_identifier' => $request->input('client_identifier')
            ]);
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
