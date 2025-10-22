<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbOrder;
use App\Models\FnbMenuItem;
use App\Models\FnbTable;
use Illuminate\Http\Request;

class FnbCustomerOrderController extends Controller
{
    /**
     * Get public menu items for a specific client (no auth required)
     */
    public function getPublicMenu(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string',
        ]);

        $menuItems = FnbMenuItem::where('client_identifier', $validated['client_identifier'])
            ->where('is_available_online', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $menuItems
        ]);
    }

    /**
     * Get table information (no auth required)
     */
    public function getTableInfo(Request $request)
    {
        $validated = $request->validate([
            'table_id' => 'required|integer',
            'client_identifier' => 'required|string',
        ]);

        $table = FnbTable::where('id', $validated['table_id'])
            ->where('client_identifier', $validated['client_identifier'])
            ->first();

        if (!$table) {
            return response()->json([
                'status' => 'error',
                'message' => 'Table not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $table
        ]);
    }

    /**
     * Submit a customer order (no auth required)
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'client_identifier' => 'required|string',
                'table_id' => 'required|integer',
                'table_name' => 'required|string',
                'customer_name' => 'nullable|string|max:255',
                'items' => 'required|array|min:1',
                'items.*.id' => 'required|integer',
                'items.*.name' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
                'subtotal' => 'required|numeric|min:0'
            ]);

            \Log::info('Customer order received', ['data' => $validated]);

            // Create order in fnb_orders with customer source and pending status
            $order = FnbOrder::create([
                'client_identifier' => $validated['client_identifier'],
                'order_source' => 'customer',
                'table_name' => $validated['table_name'],
                'customer_name' => $validated['customer_name'] ?? 'Guest',
                'items' => $validated['items'],
                'customer_notes' => $validated['notes'] ?? null,
                'discount' => 0,
                'discount_type' => 'percentage',
                'service_charge' => 0,
                'service_charge_type' => 'percentage',
                'subtotal' => $validated['subtotal'],
                'total_amount' => $validated['subtotal'],
                'status' => 'pending' // Customer orders start as pending
            ]);

            \Log::info('Customer order created', ['order_id' => $order->id]);

            // Update table status to occupied
            FnbTable::where('id', $validated['table_id'])
                ->where('client_identifier', $validated['client_identifier'])
                ->update(['status' => 'reserved']);

            return response()->json([
                'status' => 'success',
                'message' => 'Order submitted successfully',
                'data' => $order
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Customer order validation failed', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Customer order failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer orders for a specific table (for POS integration)
     */
    public function getTableOrders(Request $request, $table_id)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string'
        ]);

        $orders = FnbOrder::where('client_identifier', $validated['client_identifier'])
            ->where('table_name', $table_id) // Using table_name for consistency
            ->where('order_source', 'customer')
            ->whereIn('status', ['pending', 'active'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    /**
     * Get all pending customer orders (for POS staff)
     */
    public function getPendingOrders(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string'
        ]);

        $orders = FnbOrder::where('client_identifier', $validated['client_identifier'])
            ->where('order_source', 'customer')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    /**
     * Update customer order status
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,active,completed,cancelled',
            'client_identifier' => 'required|string'
        ]);

        $order = FnbOrder::where('id', $id)
            ->where('client_identifier', $validated['client_identifier'])
            ->where('order_source', 'customer')
            ->firstOrFail();

        $order->update(['status' => $validated['status']]);

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    /**
     * Delete a customer order (after adding to POS)
     */
    public function destroy(Request $request, $id)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string'
        ]);

        $order = FnbOrder::where('id', $id)
            ->where('client_identifier', $validated['client_identifier'])
            ->where('order_source', 'customer')
            ->where('status', 'pending') // Only delete pending orders
            ->firstOrFail();

        // Delete the order completely
        $order->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Order deleted successfully'
        ]);
    }
}
