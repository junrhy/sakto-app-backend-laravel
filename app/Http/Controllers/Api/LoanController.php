<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanPayment;
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
        return response()->json([
            'success' => true,
            'data' => ['loans' => $loans]
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
        $data['total_balance'] = $data['amount'] + ($data['amount'] * $data['interest_rate'] / 100);
        $data['paid_amount'] = 0;

        $loan = Loan::create($data);
        return response()->json([
            'success' => true,
            'data' => ['loan' => $loan]
        ]);
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
        $data['total_balance'] = $data['amount'] + ($data['amount'] * $data['interest_rate'] / 100);

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
