<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditHistory;
use App\Models\Client;
use App\Models\CreditSpentHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CreditController extends Controller
{
    /**
     * Display a listing of credit requests.
     */
    public function index()
    {
        $creditRequests = CreditHistory::where('status', 'pending')
            ->with('credit', 'client')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get all clients with their credit information
        $clientsWithCredits = Client::with(['credit', 'creditHistories', 'creditSpentHistories'])
            ->orderBy('name')
            ->get()
            ->map(function ($client) {
                $credit = $client->credit;
                $totalPurchased = $client->creditHistories()
                    ->where('status', 'approved')
                    ->sum('package_credit');
                $totalSpent = $client->creditSpentHistories()->sum('amount');
                
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'client_identifier' => $client->client_identifier,
                    'email' => $client->email,
                    'contact_number' => $client->contact_number,
                    'active' => $client->active,
                    'current_credits' => $credit ? $credit->available_credit : 0,
                    'pending_credits' => $credit ? $credit->pending_credit : 0,
                    'total_purchased' => $totalPurchased,
                    'total_spent' => $totalSpent,
                    'last_activity' => $this->getLastActivity($client),
                    'recent_history' => $this->getRecentHistory($client)
                ];
            });

        return Inertia::render('Credits/Index', [
            'creditRequests' => $creditRequests,
            'clientsWithCredits' => $clientsWithCredits
        ]);
    }

    /**
     * Accept a credit request.
     */
    public function acceptRequest(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $creditHistory = CreditHistory::findOrFail($id);

            // Check if request is still pending
            if ($creditHistory->status !== 'pending') {
                return response()->json([
                    'message' => 'This request has already been processed'
                ], 422);
            }

            // Update credit history
            $creditHistory->status = 'approved';
            $creditHistory->approved_date = now();
            $creditHistory->approved_by = Auth::user()->email;
            $creditHistory->save();

            // Update available credit
            $credit = Credit::findOrFail($creditHistory->credit_id);
            $credit->available_credit += $creditHistory->package_credit;
            $credit->pending_credit -= $creditHistory->package_credit;
            $credit->save();

            DB::commit();

            return back()->with('success', 'Credit request approved successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error processing credit request: ' . $e->getMessage());
        }
    }

    /**
     * Reject a credit request.
     */
    public function rejectRequest(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $creditHistory = CreditHistory::findOrFail($id);

            // Check if request is still pending
            if ($creditHistory->status !== 'pending') {
                return response()->json([
                    'message' => 'This request has already been processed'
                ], 422);
            }

            // Update credit history
            $creditHistory->status = 'rejected';
            $creditHistory->approved_date = now();
            $creditHistory->approved_by = Auth::user()->email;
            $creditHistory->save();

            // Update pending credit
            $credit = Credit::findOrFail($creditHistory->credit_id);
            $credit->pending_credit -= $creditHistory->package_credit;
            $credit->save();

            DB::commit();

            return back()->with('success', 'Credit request rejected successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error processing credit request: ' . $e->getMessage());
        }
    }

    /**
     * Get the last activity for a client
     */
    private function getLastActivity($client)
    {
        $lastCreditHistory = $client->creditHistories()
            ->orderBy('created_at', 'desc')
            ->first();
        
        $lastSpentHistory = $client->creditSpentHistories()
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastCreditHistory && $lastSpentHistory) {
            return $lastCreditHistory->created_at > $lastSpentHistory->created_at 
                ? $lastCreditHistory->created_at 
                : $lastSpentHistory->created_at;
        } elseif ($lastCreditHistory) {
            return $lastCreditHistory->created_at;
        } elseif ($lastSpentHistory) {
            return $lastSpentHistory->created_at;
        }

        return null;
    }

    /**
     * Get recent history for a client
     */
    private function getRecentHistory($client)
    {
        $recentCreditHistory = $client->creditHistories()
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($history) {
                return [
                    'type' => 'purchase',
                    'amount' => $history->package_credit,
                    'status' => $history->status,
                    'date' => $history->created_at,
                    'details' => $history->package_name
                ];
            });

        $recentSpentHistory = $client->creditSpentHistories()
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($history) {
                return [
                    'type' => 'spent',
                    'amount' => $history->amount,
                    'status' => $history->status,
                    'date' => $history->created_at,
                    'details' => $history->purpose
                ];
            });

        return $recentCreditHistory->concat($recentSpentHistory)
            ->sortByDesc('date')
            ->take(5)
            ->values();
    }
} 