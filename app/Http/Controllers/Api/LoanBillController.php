<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoanBill;
use Illuminate\Http\Request;

class LoanBillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $loan_id)
    {
        $clientIdentifier = $request->client_identifier;
        $bills = LoanBill::where('loan_id', $loan_id)->where('client_identifier', $clientIdentifier)->get();
        return response()->json([
            'success' => true,
            'message' => 'Bills retrieved successfully',
            'data' => ['bills' => $bills]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $loan_id)
    {
        $request['loan_id'] = $loan_id;
        $request['bill_number'] = LoanBill::where('loan_id', $loan_id)->count() + 1;
        try {
            $bill = LoanBill::create($request->all());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Bill created successfully',
            'data' => ['bill' => $bill]
        ]);
    }

    public function update(Request $request, string $id)
    {
        try {
            $bill = LoanBill::find($id);
            $bill->update($request->all());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bill updated successfully',
            'data' => ['bill' => $bill]
        ]);
    }

    public function delete(string $id)
    {
        try {
            $bill = LoanBill::find($id);
            $bill->delete();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bill deleted successfully',
        ]);
    }

    public function updateStatus(Request $request, string $id)
    {
        try {
            $bill = LoanBill::find($id);
            $bill->update(['status' => $request->status]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bill status updated successfully',
        ]);
    }
}
