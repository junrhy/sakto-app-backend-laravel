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
        $loans = Loan::where('client_identifier', $clientIdentifier)->orderBy('borrower_name', 'asc')->get();
        $loan_payments = LoanPayment::where('client_identifier', $clientIdentifier)->orderBy('payment_date', 'desc')->get();
        $loan_bills = LoanBill::where('client_identifier', $clientIdentifier)->orderBy('due_date', 'desc')->get();
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
            'status' => 'required|in:active,paid,defaulted',
            'interest_type' => 'required|in:fixed,compounding',
            'compounding_frequency' => 'required_if:interest_type,compounding|in:daily,monthly,quarterly,annually',
            'installment_frequency' => 'in:weekly,bi-weekly,monthly,quarterly,annually|nullable',
            'installment_amount' => 'nullable|numeric|min:0'
        ]);

        $data = $request->all();
        
        // Calculate number of months
        $start = strtotime($data['start_date']);
        $end = strtotime($data['end_date']);
        $days = ceil(($end - $start) / (24 * 60 * 60));
        $months = $days / 30;
        
        $principal = $data['amount'];
        
        // Calculate interest based on type
        if ($data['interest_type'] === 'fixed') {
            $data['total_interest'] = $this->calculateSimpleInterest(
                $principal,
                $data['interest_rate'],
                $months
            );
        } else {
            $data['total_interest'] = $this->calculateCompoundInterest(
                $principal,
                $data['interest_rate'],
                $data['start_date'],
                $data['end_date'],
                $data['compounding_frequency']
            );
        }
        
        $data['total_balance'] = $principal + $data['total_interest'];
        $data['paid_amount'] = 0;

        $loan = Loan::create($data);
        $loan = $loan->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Loan created successfully',
            'data' => ['loan' => $loan]
        ], 201);
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
            'status' => 'required|in:active,paid,defaulted',
            'interest_type' => 'required|in:fixed,compounding',
            'compounding_frequency' => 'required_if:interest_type,compounding|in:daily,monthly,quarterly,annually',
            'installment_frequency' => 'in:weekly,bi-weekly,monthly,quarterly,annually|nullable',
            'installment_amount' => 'nullable|numeric|min:0'
        ]);

        $data = $request->all();
        
        // Calculate number of months
        $start = strtotime($data['start_date']);
        $end = strtotime($data['end_date']);
        $days = ceil(($end - $start) / (24 * 60 * 60));
        $months = $days / 30;
        
        $principal = $data['amount'];
        
        // Calculate interest based on type
        if ($data['interest_type'] === 'fixed') {
            $data['total_interest'] = $this->calculateSimpleInterest(
                $principal,
                $data['interest_rate'],
                $months
            );
        } else {
            $data['total_interest'] = $this->calculateCompoundInterest(
                $principal,
                $data['interest_rate'],
                $data['start_date'],
                $data['end_date'],
                $data['compounding_frequency']
            );
        }
        
        $data['total_balance'] = $principal + $data['total_interest'];

        $loan = Loan::find($id);
        $data['paid_amount'] = LoanPayment::where('loan_id', $id)
            ->where('client_identifier', $loan->client_identifier)
            ->sum('amount');

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

    /**
     * Calculate simple interest
     */
    private function calculateSimpleInterest($principal, $rate, $months)
    {
        // Simple Interest Formula: I = P * r * t
        // Where: I = Interest, P = Principal, r = monthly interest rate, t = time in months
        $monthly_rate = $rate / 100; // Convert percentage to decimal
        return $principal * $monthly_rate * $months;
    }

    /**
     * Calculate compound interest
     */
    private function calculateCompoundInterest($principal, $rate, $start_date, $end_date, $frequency)
    {
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        $days = ceil(($end - $start) / (24 * 60 * 60));
        
        // Convert annual rate to the rate per period based on frequency
        $periods_per_year = [
            'daily' => 365,
            'monthly' => 12,
            'quarterly' => 4,
            'annually' => 1
        ];
        
        $n = $periods_per_year[$frequency];
        $r = ($rate / 100) / $n; // Rate per period
        $t = $days / 365; // Time in years
        
        // Compound Interest Formula: A = P(1 + r)^(nt) - P
        // Where: A = Interest, P = Principal, r = rate per period, n = number of times interest is compounded per year, t = time in years
        $total_amount = $principal * pow((1 + $r), ($n * $t));
        return $total_amount - $principal;
    }
}
