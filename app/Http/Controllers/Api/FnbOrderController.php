<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbTable;
use App\Models\FnbOrder;
use App\Models\FnbSale;
use App\Models\FnbKitchenOrder;
use Illuminate\Http\Request;

class FnbOrderController extends Controller
{
    /**
     * Get the active order for a specific table
     */
    public function getTableOrder(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string',
            'table_name' => 'required|string'
        ]);

        $order = FnbOrder::where('client_identifier', $validated['client_identifier'])
            ->where('table_name', $validated['table_name'])
            ->where('status', 'active')
            ->first();

        if (!$order) {
            return response()->json([
                'order' => null,
                'items' => [],
                'discount' => 0,
                'discount_type' => 'percentage'
            ]);
        }

        return response()->json([
            'order' => $order,
            'items' => $order->items ?? [],
            'discount' => $order->discount,
            'discount_type' => $order->discount_type
        ]);
    }

    /**
     * Save or update the order for a table
     */
    public function saveTableOrder(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string',
            'table_name' => 'required|string',
            'items' => 'required|array',
            'discount' => 'required|numeric',
            'discount_type' => 'required|in:percentage,fixed',
            'subtotal' => 'required|numeric',
            'total_amount' => 'required|numeric'
        ]);

        $order = FnbOrder::updateOrCreate(
            [
                'client_identifier' => $validated['client_identifier'],
                'table_name' => $validated['table_name'],
                'status' => 'active'
            ],
            [
                'items' => $validated['items'],
                'discount' => $validated['discount'],
                'discount_type' => $validated['discount_type'],
                'subtotal' => $validated['subtotal'],
                'total_amount' => $validated['total_amount']
            ]
        );

        // Update table status to occupied if it has items
        if (count($validated['items']) > 0) {
            FnbTable::where('name', $validated['table_name'])
                ->where('client_identifier', $validated['client_identifier'])
                ->update(['status' => 'occupied']);
        } else {
            // Set to available if no items
            FnbTable::where('name', $validated['table_name'])
                ->where('client_identifier', $validated['client_identifier'])
                ->update(['status' => 'available']);
        }

        return response()->json([
            'message' => 'Order saved successfully',
            'order' => $order
        ]);
    }

    /**
     * Complete the order (checkout)
     */
    public function completeOrder(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string',
            'table_name' => 'required|string',
            'payment_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,card',
            'change' => 'nullable|numeric|min:0'
        ]);

        $order = FnbOrder::where('client_identifier', $validated['client_identifier'])
            ->where('table_name', $validated['table_name'])
            ->where('status', 'active')
            ->first();

        if (!$order) {
            return response()->json(['message' => 'No active order found'], 404);
        }

        // Validate payment amount is sufficient
        if ($validated['payment_amount'] < $order->total_amount) {
            return response()->json([
                'message' => 'Payment amount is insufficient',
                'required' => $order->total_amount,
                'received' => $validated['payment_amount']
            ], 400);
        }

        // Update order status to completed
        $order->update(['status' => 'completed']);

        // Create sale record with payment information
        $sale = FnbSale::create([
            'table_number' => $order->table_name,
            'items' => json_encode($order->items),
            'subtotal' => $order->subtotal,
            'discount' => $order->discount,
            'discount_type' => $order->discount_type,
            'total' => $order->total_amount,
            'payment_amount' => $validated['payment_amount'],
            'payment_method' => $validated['payment_method'],
            'change_amount' => $validated['change'] ?? 0,
            'client_identifier' => $order->client_identifier
        ]);

        // Update table status to available
        FnbTable::where('name', $order->table_name)
            ->where('client_identifier', $order->client_identifier)
            ->update(['status' => 'available']);

        return response()->json([
            'message' => 'Order completed successfully',
            'sale' => $sale,
            'change' => $validated['change'] ?? 0
        ]);
    }

    /**
     * Get all active orders (for showing totals on all tables)
     */
    public function getAllActiveOrders(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string'
        ]);

        $orders = FnbOrder::where('client_identifier', $validated['client_identifier'])
            ->where('status', 'active')
            ->get()
            ->map(function ($order) {
                return [
                    'table_name' => $order->table_name,
                    'total_amount' => $order->total_amount,
                    'item_count' => count($order->items)
                ];
            });

        return response()->json(['orders' => $orders]);
    }

    /**
     * Send to kitchen
     */
    public function storeKitchenOrder(Request $request)
    {
        $validated = $request->validate([
            'table_number' => 'required',
            'items' => 'required',
            'client_identifier' => 'required'
        ]);

        FnbKitchenOrder::create([
            'table_number' => $validated['table_number'],
            'items' => json_encode($validated['items']),
            'status' => 'pending',
            'client_identifier' => $validated['client_identifier']
        ]);

        return response()->json(['message' => 'Kitchen order created successfully']);
    }

    /**
     * Get kitchen orders overview
     */
    public function getKitchenOrdersOverview(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string'
        ]);

        $kitchenOrders = FnbKitchenOrder::where('client_identifier', $validated['client_identifier'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($kitchenOrders);
    }
}
