<?php

namespace App\Http\Controllers\Api; 

use App\Http\Controllers\Controller;
use App\Models\FnbKitchenOrder;
use Illuminate\Http\Request;

class FnbKitchenOrderController extends Controller
{
    /**
     * Get all kitchen orders (with optional status filter)
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string',
            'status' => 'nullable|in:pending,preparing,ready,completed,cancelled'
        ]);

        $query = FnbKitchenOrder::where('client_identifier', $validated['client_identifier'])
            ->orderBy('created_at', 'asc'); // FIFO ordering

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        } else {
            // Default: exclude completed and cancelled
            $query->whereNotIn('status', ['completed', 'cancelled']);
        }

        $orders = $query->get();

        return response()->json([
            'data' => $orders
        ]);
    }

    /**
     * Send order to kitchen (create or update)
     */
    public function sendToKitchen(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string',
            'table_number' => 'required|string',
            'customer_name' => 'nullable|string',
            'customer_notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.id' => 'required',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric',
        ]);

        // Check if there's already a pending/preparing order for this table
        $existingOrder = FnbKitchenOrder::where('client_identifier', $validated['client_identifier'])
            ->where('table_number', $validated['table_number'])
            ->whereIn('status', ['pending', 'preparing'])
            ->first();

        if ($existingOrder) {
            // Update existing order
            $existingOrder->update([
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_notes' => $validated['customer_notes'] ?? null,
                'items' => $validated['items'],
            ]);

            return response()->json([
                'message' => 'Kitchen order updated successfully',
                'data' => $existingOrder
            ]);
        }

        // Create new kitchen order
        $orderNumber = FnbKitchenOrder::generateOrderNumber($validated['client_identifier']);

        $kitchenOrder = FnbKitchenOrder::create([
            'order_number' => $orderNumber,
            'client_identifier' => $validated['client_identifier'],
            'table_number' => $validated['table_number'],
            'customer_name' => $validated['customer_name'] ?? null,
            'customer_notes' => $validated['customer_notes'] ?? null,
            'items' => $validated['items'],
            'status' => 'pending',
            'sent_at' => now(),
        ]);

        return response()->json([
            'message' => 'Order sent to kitchen successfully',
            'data' => $kitchenOrder
        ], 201);
    }

    /**
     * Update kitchen order status
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,preparing,ready,completed,cancelled',
            'client_identifier' => 'required|string'
        ]);

        $order = FnbKitchenOrder::where('id', $id)
            ->where('client_identifier', $validated['client_identifier'])
            ->firstOrFail();

        $updateData = ['status' => $validated['status']];

        // Set timestamp based on status
        switch ($validated['status']) {
            case 'preparing':
                if (!$order->prepared_at) {
                    $updateData['prepared_at'] = now();
                }
                break;
            case 'ready':
                if (!$order->ready_at) {
                    $updateData['ready_at'] = now();
                }
                break;
            case 'completed':
                if (!$order->completed_at) {
                    $updateData['completed_at'] = now();
                }
                break;
        }

        $order->update($updateData);

        return response()->json([
            'message' => 'Status updated successfully',
            'data' => $order->fresh()
        ]);
    }

    /**
     * Complete kitchen order (when payment is done)
     */
    public function complete(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string',
            'table_number' => 'required|string'
        ]);

        $order = FnbKitchenOrder::where('client_identifier', $validated['client_identifier'])
            ->where('table_number', $validated['table_number'])
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->first();

        if ($order) {
            $order->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            return response()->json([
                'message' => 'Kitchen order completed',
                'data' => $order
            ]);
        }

        return response()->json([
            'message' => 'No active kitchen order found'
        ], 404);
    }

    /**
     * Get order by table number (for display screens)
     */
    public function getByTable(Request $request, $tableNumber)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string'
        ]);

        $order = FnbKitchenOrder::where('client_identifier', $validated['client_identifier'])
            ->where('table_number', $tableNumber)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'No active order found',
                'data' => null
            ], 404);
        }

        return response()->json([
            'data' => $order
        ]);
    }

    /**
     * Delete old completed/cancelled orders (cleanup)
     */
    public function cleanup(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string',
            'days' => 'nullable|integer|min:1|max:30'
        ]);

        $days = $validated['days'] ?? 1; // Default 1 day
        $cutoffDate = now()->subDays($days);

        $deleted = FnbKitchenOrder::where('client_identifier', $validated['client_identifier'])
            ->whereIn('status', ['completed', 'cancelled'])
            ->where('updated_at', '<', $cutoffDate)
            ->delete();

        return response()->json([
            'message' => "Cleaned up {$deleted} old orders"
        ]);
    }
}
