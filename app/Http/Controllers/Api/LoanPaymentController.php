<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Http\Request;

class LoanPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $loan_id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date'
        ]);

        $data = $request->all();
        $data['loan_id'] = $loan_id;

        $loan = Loan::find($loan_id);
        $loan->paid_amount += $data['amount'];
        $loan->save();

        $payment = LoanPayment::create($data);

        return response()->json([
            'success' => true,
            'payment' => $payment
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LoanPayment $loanPayment)
    {
        //
    }
}
