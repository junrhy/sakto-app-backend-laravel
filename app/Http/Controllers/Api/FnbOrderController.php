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
                'discount_type' => 'percentage',
                'service_charge' => 0,
                'service_charge_type' => 'percentage',
                'customer_name' => null,
                'customer_notes' => null
            ]);
        }

        return response()->json([
            'order' => $order,
            'items' => $order->items ?? [],
            'discount' => $order->discount,
            'discount_type' => $order->discount_type,
            'service_charge' => $order->service_charge ?? 0,
            'service_charge_type' => $order->service_charge_type ?? 'percentage',
            'customer_name' => $order->customer_name,
            'customer_notes' => $order->customer_notes
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
            'items' => 'array',
            'discount' => 'required|numeric',
            'discount_type' => 'required|in:percentage,fixed',
            'service_charge' => 'required|numeric',
            'service_charge_type' => 'required|in:percentage,fixed',
            'customer_name' => 'nullable|string',
            'customer_notes' => 'nullable|string',
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
                'service_charge' => $validated['service_charge'],
                'service_charge_type' => $validated['service_charge_type'],
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_notes' => $validated['customer_notes'] ?? null,
                'order_source' => !empty($validated['customer_name']) ? 'customer' : 'staff', // Automatically set order_source
                'subtotal' => $validated['subtotal'],
                'total_amount' => $validated['total_amount']
            ]
        );

        // Update table status and handle empty orders
        if (count($validated['items']) > 0) {
            // Update table status to occupied if it has items
            FnbTable::where('name', $validated['table_name'])
                ->where('client_identifier', $validated['client_identifier'])
                ->update(['status' => 'occupied']);
        } else {
            // Delete the order record if no items
            FnbOrder::where('client_identifier', $validated['client_identifier'])
                ->where('table_name', $validated['table_name'])
                ->where('status', 'active')
                ->delete();
            
            // Set table to available if no items
            FnbTable::where('name', $validated['table_name'])
                ->where('client_identifier', $validated['client_identifier'])
                ->update(['status' => 'available']);
        }

        $message = count($validated['items']) > 0 
            ? 'Order saved successfully' 
            : 'Order cleared successfully';
            
        return response()->json([
            'message' => $message,
            'order' => count($validated['items']) > 0 ? $order : null
        ]);
    }

    /**
     * Complete the order (checkout)
     */
    public function completeOrder(Request $request)
    {
        try {
            \Log::info('Complete Order Request:', $request->all());
            
            $validated = $request->validate([
                'client_identifier' => 'required|string',
                'table_name' => 'required|string',
                'payment_amount' => 'required|numeric|min:0',
                'payment_method' => 'required|string|in:cash,card',
                'change' => 'nullable|numeric|min:0'
            ]);

            \Log::info('Validated data:', $validated);

            $order = FnbOrder::where('client_identifier', $validated['client_identifier'])
                ->where('table_name', $validated['table_name'])
                ->where('status', 'active')
                ->first();

            if (!$order) {
                \Log::warning('No active order found', [
                    'client_identifier' => $validated['client_identifier'],
                    'table_name' => $validated['table_name']
                ]);
                return response()->json(['message' => 'No active order found'], 404);
            }

            \Log::info('Found order:', ['order_id' => $order->id, 'total' => $order->total_amount]);

            // Validate payment amount is sufficient
            if ($validated['payment_amount'] < $order->total_amount) {
                \Log::warning('Insufficient payment', [
                    'required' => $order->total_amount,
                    'received' => $validated['payment_amount']
                ]);
                return response()->json([
                    'message' => 'Payment amount is insufficient',
                    'required' => $order->total_amount,
                    'received' => $validated['payment_amount']
                ], 400);
            }

            // Update order status to completed
            $order->update(['status' => 'completed']);
            \Log::info('Order marked as completed', ['order_id' => $order->id]);

            // Create sale record with payment information
            $saleData = [
                'table_number' => $order->table_name,
                'items' => json_encode($order->items),
                'subtotal' => $order->subtotal,
                'discount' => $order->discount,
                'discount_type' => $order->discount_type,
                'service_charge' => $order->service_charge ?? 0,
                'service_charge_type' => $order->service_charge_type ?? 'percentage',
                'total' => $order->total_amount,
                'payment_amount' => $validated['payment_amount'],
                'payment_method' => $validated['payment_method'],
                'change_amount' => $validated['change'] ?? 0,
                'client_identifier' => $order->client_identifier
            ];
            
            \Log::info('Creating sale record:', $saleData);
            
            $sale = FnbSale::create($saleData);
            
            \Log::info('Sale record created successfully', ['sale_id' => $sale->id]);

            // Update table status to available
            FnbTable::where('name', $order->table_name)
                ->where('client_identifier', $order->client_identifier)
                ->update(['status' => 'available']);
                
            \Log::info('Table status updated to available', ['table_name' => $order->table_name]);

            return response()->json([
                'message' => 'Order completed successfully',
                'sale' => $sale,
                'change' => $validated['change'] ?? 0
            ]);
        } catch (\Exception $e) {
            \Log::error('Error completing order:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to complete order: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
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
