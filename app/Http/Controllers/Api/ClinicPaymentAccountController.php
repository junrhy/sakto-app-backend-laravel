<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicPaymentAccount;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ClinicPaymentAccountController extends Controller
{
    /**
     * Get all clinic payment accounts
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'account_type' => 'nullable|in:group,company',
            'status' => 'nullable|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $query = ClinicPaymentAccount::where('client_identifier', $validated['client_identifier'])
            ->with(['patients:id,name,clinic_payment_account_id']);

        if (isset($validated['account_type'])) {
            $query->where('account_type', $validated['account_type']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $accounts = $query->orderBy('created_at', 'desc')->get();

        // Add computed fields
        $accounts->each(function ($account) {
            $account->total_outstanding = $account->total_outstanding;
            $account->patients_count = $account->patients->count();
        });

        return response()->json([
            'success' => true,
            'data' => $accounts
        ]);
    }

    /**
     * Store a new clinic payment account
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'account_type' => ['required', Rule::in([ClinicPaymentAccount::TYPE_GROUP, ClinicPaymentAccount::TYPE_COMPANY])],
            'account_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'credit_limit' => 'nullable|numeric|min:0|max:999999999.99',
            'billing_settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // Generate unique account code
        $validated['account_code'] = ClinicPaymentAccount::generateAccountCode(
            $validated['account_type'], 
            $validated['account_name']
        );

        // Set default status
        $validated['status'] = ClinicPaymentAccount::STATUS_ACTIVE;

        try {
            $account = ClinicPaymentAccount::create($validated);
            
            return response()->json([
                'success' => true,
                'data' => $account->load('patients:id,name,clinic_payment_account_id'),
                'message' => 'Clinic payment account created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific clinic payment account
     */
    public function show(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $account = ClinicPaymentAccount::where('id', $id)
            ->where('client_identifier', $validated['client_identifier'])
            ->with([
                'patients:id,name,clinic_payment_account_id,billing_type,arn,email,phone',
                'bills',
                'bills.patient:id,name',
                'payments',
                'payments.patient:id,name'
            ])
            ->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
        }

        // Add computed fields
        $account->patients_count = $account->patients->count();
        $account->total_bills = $account->bills->sum('bill_amount');
        $account->total_payments = $account->payments->sum('payment_amount');
        $account->total_outstanding = $account->total_outstanding; // Use the model accessor

        return response()->json([
            'success' => true,
            'data' => $account
        ]);
    }

    /**
     * Update a clinic payment account
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'account_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'credit_limit' => 'nullable|numeric|min:0|max:999999999.99',
            'status' => ['nullable', Rule::in([ClinicPaymentAccount::STATUS_ACTIVE, ClinicPaymentAccount::STATUS_INACTIVE, ClinicPaymentAccount::STATUS_SUSPENDED])],
            'billing_settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $account = ClinicPaymentAccount::where('id', $id)
            ->where('client_identifier', $validated['client_identifier'])
            ->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
        }

        try {
            $account->update($validated);
            
            return response()->json([
                'success' => true,
                'data' => $account->load('patients:id,name,clinic_payment_account_id'),
                'message' => 'Account updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a clinic payment account
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $account = ClinicPaymentAccount::where('id', $id)
            ->where('client_identifier', $validated['client_identifier'])
            ->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
        }

        // Check if account has associated patients, bills, or payments
        $hasPatients = $account->patients()->exists();
        $hasBills = $account->bills()->exists();
        $hasPayments = $account->payments()->exists();

        if ($hasPatients || $hasBills || $hasPayments) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete account with associated patients, bills, or payments. Please reassign or remove them first.'
            ], 400);
        }

        try {
            $account->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign patients to a clinic payment account
     */
    public function assignPatients(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'patient_ids' => 'required|array|min:1',
            'patient_ids.*' => 'required|integer|exists:patients,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $account = ClinicPaymentAccount::where('id', $id)
            ->where('client_identifier', $validated['client_identifier'])
            ->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
        }

        try {
            // Update patients to use account billing
            Patient::whereIn('id', $validated['patient_ids'])
                ->where('client_identifier', $validated['client_identifier'])
                ->update([
                    'clinic_payment_account_id' => $account->id,
                    'billing_type' => Patient::BILLING_ACCOUNT
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Patients assigned to account successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign patients: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove patients from a clinic payment account
     */
    public function removePatients(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'patient_ids' => 'required|array|min:1',
            'patient_ids.*' => 'required|integer|exists:patients,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            // Update patients to use individual billing
            Patient::whereIn('id', $validated['patient_ids'])
                ->where('client_identifier', $validated['client_identifier'])
                ->where('clinic_payment_account_id', $id)
                ->update([
                    'clinic_payment_account_id' => null,
                    'billing_type' => Patient::BILLING_INDIVIDUAL
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Patients removed from account successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove patients: ' . $e->getMessage()
            ], 500);
        }
    }
}
