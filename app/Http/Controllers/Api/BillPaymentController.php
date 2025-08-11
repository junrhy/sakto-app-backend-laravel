<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BillPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = BillPayment::with('biller');

        // Filter by client identifier
        if ($request->has('client_identifier')) {
            $query->where('client_identifier', $request->client_identifier);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('due_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('due_date', '<=', $request->end_date);
        }

        // Search by bill title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bill_title', 'like', "%{$search}%")
                  ->orWhere('bill_description', 'like', "%{$search}%")
                  ->orWhere('bill_number', 'like', "%{$search}%");
            });
        }

        // Filter by biller name
        if ($request->has('biller_name')) {
            $billerName = $request->biller_name;
            $query->whereHas('biller', function ($q) use ($billerName) {
                $q->where('name', 'like', "%{$billerName}%");
            });
        }

        // Sort by
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $billPayments = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $billPayments->items(),
            'pagination' => [
                'current_page' => $billPayments->currentPage(),
                'last_page' => $billPayments->lastPage(),
                'per_page' => $billPayments->perPage(),
                'total' => $billPayments->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bill_title' => 'required|string|max:255',
            'bill_description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'payment_date' => 'nullable|date',
            'status' => 'nullable|in:pending,paid,overdue,cancelled,partial',
            'payment_method' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'client_identifier' => 'required|string',
            'category' => 'nullable|string|max:255',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'is_recurring' => 'nullable|boolean',
            'recurring_frequency' => 'nullable|in:daily,weekly,monthly,quarterly,yearly',
            'next_due_date' => 'nullable|date',
            'attachments' => 'nullable|array',
            'reminder_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $billPayment = BillPayment::create($request->all());

            // If this is a recurring bill and payment is made, create next bill
            if ($billPayment->is_recurring && $billPayment->status === 'paid' && $billPayment->next_due_date) {
                $this->createNextRecurringBill($billPayment);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bill payment created successfully',
                'data' => $billPayment->load('client')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create bill payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $billPayment = BillPayment::with(['client', 'biller'])->find($id);

        if (!$billPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Bill payment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $billPayment
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $billPayment = BillPayment::find($id);

        if (!$billPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Bill payment not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'bill_title' => 'sometimes|required|string|max:255',
            'bill_description' => 'nullable|string',
            'amount' => 'sometimes|required|numeric|min:0',
            'due_date' => 'sometimes|required|date',
            'payment_date' => 'nullable|date',
            'status' => 'nullable|in:pending,paid,overdue,cancelled,partial',
            'payment_method' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'is_recurring' => 'nullable|boolean',
            'recurring_frequency' => 'nullable|in:daily,weekly,monthly,quarterly,yearly',
            'next_due_date' => 'nullable|date',
            'attachments' => 'nullable|array',
            'reminder_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldStatus = $billPayment->status;
            $billPayment->update($request->all());

            // If status changed to paid and it's a recurring bill, create next bill
            if ($oldStatus !== 'paid' && $billPayment->status === 'paid' && 
                $billPayment->is_recurring && $billPayment->next_due_date) {
                $this->createNextRecurringBill($billPayment);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bill payment updated successfully',
                'data' => $billPayment->load('client')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update bill payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $billPayment = BillPayment::find($id);

        if (!$billPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Bill payment not found'
            ], 404);
        }

        try {
            $billPayment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bill payment deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete bill payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bill payment statistics.
     */
    public function statistics(Request $request)
    {
        $clientIdentifier = $request->get('client_identifier');

        $query = BillPayment::query();
        if ($clientIdentifier) {
            $query->where('client_identifier', $clientIdentifier);
        }

        $statistics = [
            'total_bills' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'paid_bills' => $query->where('status', 'paid')->count(),
            'paid_amount' => $query->where('status', 'paid')->sum('amount'),
            'pending_bills' => $query->where('status', 'pending')->count(),
            'pending_amount' => $query->where('status', 'pending')->sum('amount'),
            'overdue_bills' => $query->overdue()->count(),
            'overdue_amount' => $query->overdue()->sum('amount'),
            'upcoming_bills' => $query->upcoming()->count(),
            'upcoming_amount' => $query->upcoming()->sum('amount'),
        ];

        // Category breakdown
        $categoryStats = $query->select('category', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
            ->groupBy('category')
            ->get();

        // Priority breakdown
        $priorityStats = $query->select('priority', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
            ->groupBy('priority')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => $statistics,
                'category_breakdown' => $categoryStats,
                'priority_breakdown' => $priorityStats,
            ]
        ]);
    }

    /**
     * Get overdue bills.
     */
    public function overdue(Request $request)
    {
        $query = BillPayment::with('biller')->overdue();

        if ($request->has('client_identifier')) {
            $query->where('client_identifier', $request->client_identifier);
        }

        $perPage = $request->get('per_page', 15);
        $overdueBills = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $overdueBills->items(),
            'pagination' => [
                'current_page' => $overdueBills->currentPage(),
                'last_page' => $overdueBills->lastPage(),
                'per_page' => $overdueBills->perPage(),
                'total' => $overdueBills->total(),
            ]
        ]);
    }

    /**
     * Get upcoming bills.
     */
    public function upcoming(Request $request)
    {
        $days = $request->get('days', 7);
        $query = BillPayment::with('biller')->upcoming($days);

        if ($request->has('client_identifier')) {
            $query->where('client_identifier', $request->client_identifier);
        }

        $perPage = $request->get('per_page', 15);
        $upcomingBills = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $upcomingBills->items(),
            'pagination' => [
                'current_page' => $upcomingBills->currentPage(),
                'last_page' => $upcomingBills->lastPage(),
                'per_page' => $upcomingBills->perPage(),
                'total' => $upcomingBills->total(),
            ]
        ]);
    }

    /**
     * Bulk update bill status.
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bill_ids' => 'required|array',
            'bill_ids.*' => 'exists:bill_payments,id',
            'status' => 'required|in:pending,paid,overdue,cancelled,partial',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $updatedCount = BillPayment::whereIn('id', $request->bill_ids)
                ->update(['status' => $request->status]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} bills",
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update bills',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete bills.
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bill_ids' => 'required|array',
            'bill_ids.*' => 'exists:bill_payments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $deletedCount = BillPayment::whereIn('id', $request->bill_ids)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} bills",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete bills',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create next recurring bill.
     */
    private function createNextRecurringBill(BillPayment $billPayment)
    {
        $nextBill = $billPayment->replicate();
        $nextBill->payment_date = null;
        $nextBill->status = 'pending';
        $nextBill->reminder_sent = false;
        $nextBill->reminder_date = null;
        $nextBill->due_date = $billPayment->next_due_date;
        
        // Calculate next due date based on frequency
        $nextBill->next_due_date = $this->calculateNextDueDate(
            $billPayment->next_due_date,
            $billPayment->recurring_frequency
        );

        $nextBill->save();
    }

    /**
     * Calculate next due date based on frequency.
     */
    private function calculateNextDueDate($currentDate, $frequency)
    {
        $date = \Carbon\Carbon::parse($currentDate);

        return match($frequency) {
            'daily' => $date->addDay(),
            'weekly' => $date->addWeek(),
            'monthly' => $date->addMonth(),
            'quarterly' => $date->addMonths(3),
            'yearly' => $date->addYear(),
            default => $date->addMonth(),
        };
    }
}
