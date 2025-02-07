<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditHistory;
use App\Models\CreditSpentHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CreditController extends Controller
{
    /**
     * Get credit balance for a client
     */
    public function getBalance($clientIdentifier)
    {
        $credit = Credit::where('client_identifier', $clientIdentifier)->first();
        
        if (!$credit) {
            return response()->json([
                'message' => 'Credit record not found'
            ], 404);
        }

        return response()->json($credit);
    }

    /**
     * Request to add credits
     */
    public function requestCredit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'package_name' => 'required|string',
            'package_credit' => 'required|integer',
            'package_amount' => 'required|integer',
            'payment_method' => 'required|string',
            'payment_method_details' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $credit = Credit::firstOrCreate(
            ['client_identifier' => $request->client_identifier],
            [
                'available_credit' => 0,
                'pending_credit' => 0
            ]
        );        

        $creditHistory = new CreditHistory([
            'credit_id' => $credit->id,
            'client_identifier' => $request->client_identifier,
            'package_name' => $request->package_name,
            'package_credit' => $request->package_credit,
            'package_amount' => $request->package_amount,
            'payment_method' => $request->payment_method,
            'payment_method_details' => $request->payment_method_details,
            'transaction_id' => $request->transaction_id,
            'status' => 'pending'
        ]);

        $creditHistory->save();
        
        // Update pending credits
        $credit->pending_credit += $request->package_credit;
        $credit->save();

        return response()->json([
            'message' => 'Credit request submitted successfully',
            'credit_history' => $creditHistory
        ], 201);
    }

    /**
     * Approve credit request
     */
    public function approveCredit(Request $request, $id)
    {
        $creditHistory = CreditHistory::findOrFail($id);
        
        if ($creditHistory->status !== 'pending') {
            return response()->json([
                'message' => 'Credit request is not in pending status'
            ], 400);
        }

        $credit = Credit::where('client_identifier', $creditHistory->client_identifier)->first();
        
        if (!$credit) {
            return response()->json([
                'message' => 'Credit record not found'
            ], 404);
        }

        // Update credit history
        $creditHistory->status = 'approved';
        $creditHistory->approved_date = now();
        $creditHistory->approved_by = Auth::id();
        $creditHistory->save();

        // Update credit balance
        $credit->available_credit += $creditHistory->package_credit;
        $credit->pending_credit -= $creditHistory->package_credit;
        $credit->save();

        return response()->json([
            'message' => 'Credit request approved successfully',
            'credit' => $credit,
            'credit_history' => $creditHistory
        ]);
    }

    /**
     * Reject credit request
     */
    public function rejectCredit(Request $request, $id)
    {
        $creditHistory = CreditHistory::findOrFail($id);
        
        if ($creditHistory->status !== 'pending') {
            return response()->json([
                'message' => 'Credit request is not in pending status'
            ], 400);
        }

        $credit = Credit::where('client_identifier', $creditHistory->client_identifier)->first();
        
        if (!$credit) {
            return response()->json([
                'message' => 'Credit record not found'
            ], 404);
        }

        // Update credit history
        $creditHistory->status = 'rejected';
        $creditHistory->save();

        // Update pending credits
        $credit->pending_credit -= $creditHistory->package_credit;
        $credit->save();

        return response()->json([
            'message' => 'Credit request rejected successfully',
            'credit' => $credit,
            'credit_history' => $creditHistory
        ]);
    }

    /**
     * Get credit history for a client
     */
    public function getCreditHistory($clientIdentifier)
    {
        $creditHistories = CreditHistory::where('client_identifier', $clientIdentifier)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($creditHistories);
    }

    /**
     * Spend credits
     */
    public function spendCredit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'purpose' => 'required|string',
            'reference_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $credit = Credit::where('client_identifier', $request->client_identifier)->first();
        
        if (!$credit) {
            return response()->json([
                'message' => 'Credit record not found'
            ], 404);
        }

        if ($credit->available_credit < $request->amount) { 
            return response()->json([
                'message' => 'Insufficient credits'
            ], 400);
        }

        $credit->available_credit -= $request->amount;
        $credit->save();

        $creditSpentHistory = new CreditSpentHistory([
            'credit_id' => $credit->id,
            'client_identifier' => $request->client_identifier,
            'amount' => $request->amount,
            'purpose' => $request->purpose,
            'reference_id' => $request->reference_id,
            'status' => 'spent'
        ]);
        $creditSpentHistory->save();

        return response()->json([
            'success' => true,
            'message' => 'Credits spent successfully',
            'credit' => $credit
        ]);
    }

    /**
     * Get spent credit history for a client
     */
    public function getSpentCreditHistory($clientIdentifier)
    {
        $creditSpentHistories = CreditSpentHistory::where('client_identifier', $clientIdentifier)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($creditSpentHistories);
    }
}
