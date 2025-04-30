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
            'frequency' => 'required|in:daily,monthly,quarterly,annually',
            'installment_frequency' => 'in:weekly,bi-weekly,monthly,quarterly,annually|nullable',
            'installment_amount' => 'nullable|numeric|min:0'
        ]);

        $data = $request->all();
        
        // Calculate time difference
        $start = new \DateTime($data['start_date']);
        $end = new \DateTime($data['end_date']);
        $interval = $start->diff($end);
        $days = $interval->days;
        
        $principal = $data['amount'];
        
        // Calculate interest based on type
        if ($data['interest_type'] === 'fixed') {
            $data['total_interest'] = $this->calculateSimpleInterest(
                $principal,
                $data['interest_rate'],
                $days,
                $data['frequency']
            );
        } else {
            $data['total_interest'] = $this->calculateCompoundInterest(
                $principal,
                $data['interest_rate'],
                $data['start_date'],
                $data['end_date'],
                $data['frequency']
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
            'frequency' => 'required|in:daily,monthly,quarterly,annually',
            'installment_frequency' => 'in:weekly,bi-weekly,monthly,quarterly,annually|nullable',
            'installment_amount' => 'nullable|numeric|min:0'
        ]);

        $data = $request->all();
        
        // Calculate time difference
        $start = new \DateTime($data['start_date']);
        $end = new \DateTime($data['end_date']);
        $interval = $start->diff($end);
        $days = $interval->days;
        
        $principal = $data['amount'];
        
        // Calculate interest based on type
        if ($data['interest_type'] === 'fixed') {
            $data['total_interest'] = $this->calculateSimpleInterest(
                $principal,
                $data['interest_rate'],
                $days,
                $data['frequency']
            );
        } else {
            $data['total_interest'] = $this->calculateCompoundInterest(
                $principal,
                $data['interest_rate'],
                $data['start_date'],
                $data['end_date'],
                $data['frequency']
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
     * Calculate simple interest with frequency
     * @param float $principal The loan amount
     * @param float $rate Annual interest rate as a percentage
     * @param int $days Number of days
     * @param string $frequency Interest calculation frequency (daily, monthly, quarterly, annually)
     * @return float The calculated interest amount
     */
    private function calculateSimpleInterest($principal, $rate, $days, $frequency)
    {
        // Convert annual rate to decimal
        $annual_rate = $rate / 100;
        
        // Calculate time in years
        $years = $days / 365;
        
        // Calculate total interest based on frequency
        switch ($frequency) {
            case 'daily':
                // Daily interest: (P * r * t) / 365
                $daily_rate = $annual_rate / 365;
                return $principal * $daily_rate * $days;
                
            case 'monthly':
                // Monthly interest: (P * r * t) / 12
                $monthly_rate = $annual_rate / 12;
                $months = $days / 30.44; // Average days per month
                return $principal * $monthly_rate * $months;
                
            case 'quarterly':
                // Quarterly interest: (P * r * t) / 4
                $quarterly_rate = $annual_rate / 4;
                $quarters = $days / 91.32; // Average days per quarter
                return $principal * $quarterly_rate * $quarters;
                
            case 'annually':
                // Annual interest: P * r * t
                return $principal * $annual_rate * $years;
                
            default:
                throw new \InvalidArgumentException('Invalid frequency specified');
        }
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
