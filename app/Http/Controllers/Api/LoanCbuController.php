<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CbuFund;
use App\Models\CbuContribution;
use App\Models\CbuHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LoanCbuController extends Controller
{
    /**
     * Get all CBU funds
     */
    public function getCbuFunds(Request $request)
    {
        $funds = CbuFund::with(['contributions', 'history'])->where('client_identifier', $request->client_identifier)->get();
        return response()->json([
            'success' => true,
            'message' => 'CBU funds fetched successfully',
            'data' => [
                'cbu_funds' => $funds
            ]
        ]);
    }

    /**
     * Store a new CBU fund
     */
    public function storeCbuFund(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $fund = CbuFund::create($request->all());
        return response()->json($fund, 201);
    }

    /**
     * Update an existing CBU fund
     */
    public function updateCbuFund(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'target_amount' => 'numeric|min:0',
            'start_date' => 'date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $fund = CbuFund::findOrFail($id);
        $fund->update($request->all());
        return response()->json($fund);
    }

    /**
     * Delete a CBU fund
     */
    public function destroyCbuFund($id)
    {
        $fund = CbuFund::findOrFail($id);
        $fund->delete();
        return response()->json(null, 204);
    }

    /**
     * Add a new CBU contribution
     */
    public function addCbuContribution(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'cbu_fund_id' => 'required|exists:cbu_funds,id',
            'amount' => 'required|numeric|min:0',
            'contribution_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $contribution = CbuContribution::create($request->all());
            
            // Update fund total
            $fund = CbuFund::findOrFail($request->cbu_fund_id);
            $fund->total_amount += $request->amount;
            $fund->save();

            // Record in history
            CbuHistory::create([
                'cbu_fund_id' => $request->cbu_fund_id,
                'action' => 'contribution',
                'amount' => $request->amount,
                'notes' => $request->notes,
                'date' => $request->contribution_date,
                'client_identifier' => $request->client_identifier,
            ]);

            DB::commit();
            return response()->json($contribution, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all CBU contributions
     */
    public function getCbuContributions(Request $request, $id)
    {
        $contributions = CbuContribution::with('fund')
        ->where('cbu_fund_id', $id)
        ->where('client_identifier', $request->client_identifier)
        ->get();
        return response()->json([
            'success' => true,
            'message' => 'CBU contributions fetched successfully',
            'data' => [
                'cbu_contributions' => $contributions
            ]
        ]);
    }

    /** 
     * Get all CBU withdrawals
     */
    public function getCbuWithdrawals(Request $request, $id)
    {
        $withdrawals = CbuHistory::where('cbu_fund_id', $id)
        ->where('client_identifier', $request->client_identifier)
        ->where('action', 'withdrawal')
        ->get();
        return response()->json([
            'success' => true,
            'message' => 'CBU withdrawals fetched successfully',
            'data' => [
                'cbu_withdrawals' => $withdrawals
            ]
        ]);
    }

    /**
     * Process a CBU withdrawal request
     */
    public function withdrawCbuFund(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cbu_fund_id' => 'required|exists:cbu_funds,id',
            'amount' => 'required|numeric|min:0',
            'notes' => 'required|string',
            'withdrawal_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $fund = CbuFund::findOrFail($request->cbu_fund_id);
        
        if ($fund->total_amount < $request->amount) {
            return response()->json(['error' => 'Insufficient funds'], 400);
        }

        DB::beginTransaction();
        try {
            // Record withdrawal in history
            CbuHistory::create([
                'cbu_fund_id' => $request->cbu_fund_id,
                'action' => 'withdrawal_request',
                'amount' => $request->amount,
                'notes' => $request->notes,
                'date' => $request->withdrawal_date,
                'client_identifier' => $request->client_identifier,
            ]);

            DB::commit();
            return response()->json(['message' => 'Withdrawal request submitted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to process withdrawal request'], 500);
        }
    }

    /**
     * Process an approved CBU withdrawal
     */
    public function processCbuWithdrawal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cbu_fund_id' => 'required|exists:cbu_funds,id',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'withdrawal_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $fund = CbuFund::findOrFail($request->cbu_fund_id);
        
        if ($fund->total_amount < $request->amount) {
            return response()->json(['error' => 'Insufficient funds'], 400);
        }

        DB::beginTransaction();
        try {
            // Update fund total
            $fund->total_amount -= $request->amount;
            $fund->save();

            // Record in history
            CbuHistory::create([
                'cbu_fund_id' => $request->cbu_fund_id,
                'action' => 'withdrawal',
                'amount' => $request->amount,
                'notes' => $request->notes,
                'date' => $request->withdrawal_date,
                'client_identifier' => $request->client_identifier,
            ]);

            DB::commit();
            return response()->json(['message' => 'Withdrawal processed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to process withdrawal'], 500);
        }
    }

    /**
     * Get CBU history
     */
    public function getCbuHistory()
    {
        $history = CbuHistory::with('fund')
            ->orderBy('date', 'desc')
            ->get();
        return response()->json($history);
    }

    /**
     * Generate CBU report
     */
    public function generateCbuReport()
    {
        $report = [
            'total_funds' => CbuFund::count(),
            'total_contributions' => CbuContribution::sum('amount'),
            'total_withdrawals' => CbuHistory::where('action', 'withdrawal')->sum('amount'),
            'active_funds' => CbuFund::where('end_date', '>', now())->count(),
            'recent_activities' => CbuHistory::with('fund')
                ->orderBy('date', 'desc')
                ->take(10)
                ->get(),
        ];

        return response()->json($report);
    }
}
