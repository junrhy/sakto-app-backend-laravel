<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanBill;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $loans = Loan::where('client_identifier', $clientIdentifier)->get();
        $loan_payments = LoanPayment::where('client_identifier', $clientIdentifier)->get();
        $loan_bills = LoanBill::where('client_identifier', $clientIdentifier)->get();
        return response()->json([
            'success' => true,
            'data' => ['loans' => $loans, 'loan_payments' => $loan_payments, 'loan_bills' => $loan_bills]
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'borrower_name' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'compounding_frequency' => 'required|in:daily,monthly,quarterly,annually',
            'status' => 'required|in:active,paid,defaulted'
        ]);

        $data = $request->all();
        
        // Calculate the number of periods based on compounding frequency
        $start = strtotime($data['start_date']);
        $end = strtotime($data['end_date']);
        $years = ($end - $start) / (365 * 24 * 60 * 60);
        
        $n = match($data['compounding_frequency']) {
            'daily' => 365,
            'monthly' => 12,
            'quarterly' => 4,
            'annually' => 1,
        };

        // Compound Interest Formula: A = P(1 + r/n)^(nt) - P
        // Where: A = Total Interest, P = Principal, r = Interest Rate, n = Compounds per year, t = Time in years
        $principal = $data['amount'];
        $rate = $data['interest_rate'] / 100;
        $data['total_interest'] = $principal * (pow(1 + ($rate / $n), ($n * $years))) - $principal;
        $data['total_balance'] = $principal + $data['total_interest'];
        $data['paid_amount'] = 0;

        $loan = Loan::create($data);
        
        // Load any relationships if needed
        $loan = $loan->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Loan created successfully',
            'data' => ['loan' => $loan]
        ], 201);  // Using 201 Created status code
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'borrower_name' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'compounding_frequency' => 'required|in:daily,monthly,quarterly,annually',
            'status' => 'required|in:active,paid,defaulted'
        ]);

        $data = $request->all();

        // Calculate the number of periods based on compounding frequency
        $start = strtotime($data['start_date']);
        $end = strtotime($data['end_date']);
        $years = ($end - $start) / (365 * 24 * 60 * 60);
        
        $n = match($data['compounding_frequency']) {
            'daily' => 365,
            'monthly' => 12,
            'quarterly' => 4,
            'annually' => 1,
        };
 
        // Compound Interest Formula: A = P(1 + r/n)^(nt) - P
        // Where: A = Total Interest, P = Principal, r = Interest Rate, n = Compounds per year, t = Time in years
        $principal = $data['amount'];
        $rate = $data['interest_rate'] / 100;
        $data['total_interest'] = $principal * (pow(1 + ($rate / $n), ($n * $years))) - $principal;
        $data['total_balance'] = $principal + $data['total_interest'];

        $loan = Loan::find($id);
        $data['paid_amount'] = LoanPayment::where('loan_id', $id)->where('client_identifier', $loan->client_identifier)->sum('amount');

        $loan->update($data);
        return response()->json([
            'success' => true,
            'data' => ['loan' => $loan]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Loan::find($id)->delete();
        return response()->json([
            'success' => true
        ]);
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array'
        ]);
        Loan::whereIn('id', $request->ids)->delete();
        return response()->json([
            'success' => true
        ]);
    }
}
