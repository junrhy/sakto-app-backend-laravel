<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CbuFund;
use App\Models\CbuContribution;
use App\Models\CbuHistory;
use App\Models\CbuDividend;
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
    public function getCbuHistory(Request $request, $id)
    {
        $history = CbuHistory::with('fund')
        ->where('cbu_fund_id', $id)
        ->where('client_identifier', $request->client_identifier)
        ->orderBy('date', 'desc')
        ->get();
        return response()->json($history);
    }

    /**
     * Generate CBU report
     */
    public function generateCbuReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $report = [
            'total_funds' => CbuFund::where('client_identifier', $request->client_identifier)
                ->whereBetween('created_at', [$request->start_date, $request->end_date])
                ->count(),
            'total_contributions' => CbuContribution::where('client_identifier', $request->client_identifier)
                ->whereBetween('contribution_date', [$request->start_date, $request->end_date])
                ->sum('amount'),
            'total_withdrawals' => CbuHistory::where('action', 'withdrawal')
                ->where('client_identifier', $request->client_identifier)
                ->whereBetween('date', [$request->start_date, $request->end_date])
                ->sum('amount'),
            'total_dividends' => CbuDividend::where('client_identifier', $request->client_identifier)
                ->whereBetween('dividend_date', [$request->start_date, $request->end_date])
                ->sum('amount'),
            'active_funds' => CbuFund::where('end_date', '>', now())
                ->where('client_identifier', $request->client_identifier)
                ->whereBetween('created_at', [$request->start_date, $request->end_date])
                ->count(),
            'recent_activities' => CbuHistory::with('fund')
                ->where('client_identifier', $request->client_identifier)
                ->whereBetween('date', [$request->start_date, $request->end_date])
                ->orderBy('date', 'desc')
                ->take(10)
                ->get(),
            'recent_dividends' => CbuDividend::with('fund')
                ->where('client_identifier', $request->client_identifier)
                ->whereBetween('dividend_date', [$request->start_date, $request->end_date])
                ->orderBy('dividend_date', 'desc')
                ->take(5)
                ->get(),
        ];

        return response()->json($report);
    }

    /**
     * Get all dividends for a specific CBU fund
     *
     * @param string $id The CBU fund ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCbuDividends(string $id)
    {
        try {
            $cbuFund = CbuFund::findOrFail($id);
            $dividends = CbuDividend::where('cbu_fund_id', $id)
                ->orderBy('dividend_date', 'desc')
                ->get();

            return response()->json([
                'data' => [
                    'cbu_dividends' => $dividends
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch CBU dividends.'], 500);
        }
    }

    /**
     * Add a new dividend to a CBU fund
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id The CBU fund ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function addCbuDividend(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'cbu_fund_id' => 'required|exists:cbu_funds,id',
                'amount' => 'required|numeric|min:0',
                'dividend_date' => 'required|date',
                'notes' => 'nullable|string',
                'client_identifier' => 'required|string',
            ]);

            $cbuFund = CbuFund::findOrFail($id);

            DB::beginTransaction();
            try {
                // Create the dividend record
                $dividend = CbuDividend::create([
                    'cbu_fund_id' => $id,
                    'amount' => $validated['amount'],
                    'dividend_date' => $validated['dividend_date'],
                    'notes' => $validated['notes'] ?? null,
                    'client_identifier' => $validated['client_identifier'],
                ]);

                // Update the CBU fund's total amount
                $cbuFund->total_amount = bcadd($cbuFund->total_amount, $validated['amount'], 2);
                $cbuFund->save();

                // Record in history
                CbuHistory::create([
                    'cbu_fund_id' => $id,
                    'action' => 'dividend',
                    'amount' => $validated['amount'],
                    'notes' => $validated['notes'] ?? null,
                    'date' => $validated['dividend_date'],
                    'client_identifier' => $validated['client_identifier'],
                ]);

                DB::commit();

                return response()->json([
                    'data' => [
                        'cbu_dividend' => $dividend,
                        'message' => 'Dividend added successfully'
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add CBU dividend.'], 500);
        }
    }
}
