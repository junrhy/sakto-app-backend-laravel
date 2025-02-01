<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\CreditHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CreditAdminController extends Controller
{
    /**
     * Display a listing of credit requests.
     */
    public function index()
    {
        $creditRequests = CreditHistory::where('status', 'pending')
            ->with('credit')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Credits/Index', [
            'creditRequests' => $creditRequests
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
}
