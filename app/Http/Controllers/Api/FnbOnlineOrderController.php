<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbOnlineOrder;
use App\Models\FnbOnlineStore;
use App\Models\FnbMenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FnbOnlineOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $status = $request->query('status');
        $verificationStatus = $request->query('verification_status');
        $paymentStatus = $request->query('payment_status');
        
        $query = FnbOnlineOrder::where('client_identifier', $clientIdentifier)
            ->with('onlineStore');
            
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($verificationStatus) {
            $query->where('verification_status', $verificationStatus);
        }
        
        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'FNB Online Orders retrieved successfully',
            'data' => [
                'fnb_online_orders' => $orders
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'online_store_id' => 'required|exists:fnb_online_stores,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'delivery_address' => 'required|string',
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:fnb_menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'client_identifier' => 'required|string'
        ]);

        // Verify the online store belongs to the client
        $onlineStore = FnbOnlineStore::where('id', $validated['online_store_id'])
            ->where('client_identifier', $validated['client_identifier'])
            ->where('is_active', true)
            ->first();

        if (!$onlineStore) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online store not found or inactive'
            ], 404);
        }

        // Check if payment negotiation is enabled for this store
        $validated['payment_negotiation_enabled'] = $onlineStore->payment_negotiation_enabled;

        $validated['order_number'] = FnbOnlineOrder::generateOrderNumber();
        
        $order = FnbOnlineOrder::create($validated);
        
        // Clear cache when orders are created
        cache()->forget("fnb_online_orders_{$validated['client_identifier']}");
        
        return response()->json([
            'status' => 'success',
            'message' => 'Online order created successfully',
            'data' => [
                'fnb_online_order' => $order->load('onlineStore')
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        
        $order = FnbOnlineOrder::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->with('onlineStore')
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online order not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'FNB Online Order retrieved successfully',
            'data' => [
                'fnb_online_order' => $order
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        
        $order = FnbOnlineOrder::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online order not found'
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,verified,preparing,ready,delivered,cancelled',
            'verification_status' => 'sometimes|in:pending,verified,rejected',
            'verification_notes' => 'nullable|string',
            'payment_status' => 'sometimes|in:pending,negotiated,paid,failed',
            'payment_notes' => 'nullable|string',
            'negotiated_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
        ]);

        // Use database transaction to ensure update is committed
        \DB::transaction(function () use ($order, $validated) {
            $order->update($validated);
        });
        
        // Refresh the order to get updated data
        $order->refresh();
        
        // Clear cache when orders are modified
        cache()->forget("fnb_online_orders_{$clientIdentifier}");
        
        return response()->json([
            'status' => 'success',
            'message' => 'Online order updated successfully',
            'data' => [
                'fnb_online_order' => $order->load('onlineStore')
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        
        $order = FnbOnlineOrder::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online order not found'
            ], 404);
        }

        $order->delete();
        
        // Clear cache when orders are deleted
        cache()->forget("fnb_online_orders_{$clientIdentifier}");
        
        return response()->noContent();
    }

    /**
     * Verify an online order
     */
    public function verifyOrder(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        
        $order = FnbOnlineOrder::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online order not found'
            ], 404);
        }

        if (!$order->canBeVerified()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order cannot be verified at this time'
            ], 422);
        }

        $validated = $request->validate([
            'verification_status' => 'required|in:verified,rejected',
            'verification_notes' => 'nullable|string',
        ]);

        $order->update([
            'verification_status' => $validated['verification_status'],
            'verification_notes' => $validated['verification_notes'],
            'verified_at' => now(),
        ]);

        // If verified and no payment negotiation, mark as verified status
        if ($validated['verification_status'] === 'verified' && !$order->payment_negotiation_enabled) {
            $order->update(['status' => 'verified']);
        }
        
        // Clear cache when orders are modified
        cache()->forget("fnb_online_orders_{$clientIdentifier}");
        
        return response()->json([
            'status' => 'success',
            'message' => 'Order verification updated successfully',
            'data' => [
                'fnb_online_order' => $order->load('onlineStore')
            ]
        ]);
    }

    /**
     * Negotiate payment for an order
     */
    public function negotiatePayment(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        
        $order = FnbOnlineOrder::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online order not found'
            ], 404);
        }

        if (!$order->payment_negotiation_enabled) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment negotiation is not enabled for this order'
            ], 422);
        }

        $validated = $request->validate([
            'negotiated_amount' => 'required|numeric|min:0|max:' . $order->total_amount,
            'payment_notes' => 'nullable|string',
        ]);

        $order->update([
            'negotiated_amount' => $validated['negotiated_amount'],
            'payment_notes' => $validated['payment_notes'],
            'payment_status' => 'negotiated',
        ]);
        
        // Clear cache when orders are modified
        cache()->forget("fnb_online_orders_{$clientIdentifier}");
        
        return response()->json([
            'status' => 'success',
            'message' => 'Payment negotiation updated successfully',
            'data' => [
                'fnb_online_order' => $order->load('onlineStore')
            ]
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        
        $order = FnbOnlineOrder::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online order not found'
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,verified,preparing,ready,delivered,cancelled',
        ]);

        $newStatus = $validated['status'];
        
        // Validate status transitions
        if ($newStatus === 'preparing' && !$order->canBePrepared()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order cannot be prepared at this time'
            ], 422);
        }

        if ($newStatus === 'delivered' && !$order->canBeDelivered()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order cannot be delivered at this time'
            ], 422);
        }

        $updateData = ['status' => $newStatus];
        
        // Set timestamps based on status
        switch ($newStatus) {
            case 'preparing':
                $updateData['preparing_at'] = now();
                break;
            case 'ready':
                $updateData['ready_at'] = now();
                break;
            case 'delivered':
                $updateData['delivered_at'] = now();
                break;
        }

        $order->update($updateData);
        
        // Clear cache when orders are modified
        cache()->forget("fnb_online_orders_{$clientIdentifier}");
        
        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'data' => [
                'fnb_online_order' => $order->load('onlineStore')
            ]
        ]);
    }

    /**
     * Get orders pending verification
     */
    public function getPendingVerification(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        
        $orders = FnbOnlineOrder::where('client_identifier', $clientIdentifier)
            ->pendingVerification()
            ->with('onlineStore')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Pending verification orders retrieved successfully',
            'data' => [
                'fnb_online_orders' => $orders
            ]
        ]);
    }

    /**
     * Get orders pending payment negotiation
     */
    public function getPendingPaymentNegotiation(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        
        $orders = FnbOnlineOrder::where('client_identifier', $clientIdentifier)
            ->pendingPaymentNegotiation()
            ->with('onlineStore')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Pending payment negotiation orders retrieved successfully',
            'data' => [
                'fnb_online_orders' => $orders
            ]
        ]);
    }

    /**
     * Create a public online order (no authentication required)
     */
    public function createPublicOrder(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'delivery_address' => 'required|string',
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:fnb_menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
        ]);

        // Find the online store by domain
        $onlineStore = FnbOnlineStore::where('domain', $validated['domain'])
            ->where('is_active', true)
            ->first();

        if (!$onlineStore) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online store not found or inactive'
            ], 404);
        }

        // Verify all menu items belong to the store and are available online
        $validMenuItems = FnbMenuItem::whereIn('id', collect($validated['items'])->pluck('id'))
            ->where('client_identifier', $onlineStore->client_identifier)
            ->where('is_available_online', true)
            ->get();

        if ($validMenuItems->count() !== count($validated['items'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some menu items are not available online'
            ], 422);
        }

        // Create the order
        $orderData = [
            'client_identifier' => $onlineStore->client_identifier,
            'online_store_id' => $onlineStore->id,
            'order_number' => FnbOnlineOrder::generateOrderNumber(),
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'delivery_address' => $validated['delivery_address'],
            'items' => $validated['items'],
            'subtotal' => $validated['subtotal'],
            'delivery_fee' => $validated['delivery_fee'] ?? 0,
            'tax_amount' => $validated['tax_amount'] ?? 0,
            'total_amount' => $validated['total_amount'],
            'payment_negotiation_enabled' => $onlineStore->payment_negotiation_enabled,
        ];

        $order = FnbOnlineOrder::create($orderData);
        
        // Clear cache when orders are created
        cache()->forget("fnb_online_orders_{$onlineStore->client_identifier}");
        
        return response()->json([
            'status' => 'success',
            'message' => 'Order created successfully',
            'data' => [
                'order_number' => $order->order_number,
                'id' => $order->id,
                'status' => $order->status,
                'verification_status' => $order->verification_status,
                'payment_negotiation_enabled' => $order->payment_negotiation_enabled,
                'total_amount' => $order->total_amount,
            ]
        ], 201);
    }

    /**
     * Get public order by order number (no authentication required)
     */
    public function getPublicOrder(Request $request, $orderNumber)
    {
        $domain = $request->query('domain');
        
        if (!$domain) {
            return response()->json([
                'status' => 'error',
                'message' => 'Domain is required'
            ], 400);
        }

        // Find the online store by domain
        $onlineStore = FnbOnlineStore::where('domain', $domain)
            ->where('is_active', true)
            ->first();

        if (!$onlineStore) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online store not found or inactive'
            ], 404);
        }

        // Find the order
        $order = FnbOnlineOrder::where('order_number', $orderNumber)
            ->where('online_store_id', $onlineStore->id)
            ->with('onlineStore')
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order retrieved successfully',
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'delivery_address' => $order->delivery_address,
                'items' => $order->items,
                'subtotal' => $order->subtotal,
                'delivery_fee' => $order->delivery_fee,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
                'negotiated_amount' => $order->negotiated_amount,
                'status' => $order->status,
                'verification_status' => $order->verification_status,
                'verification_notes' => $order->verification_notes,
                'payment_status' => $order->payment_status,
                'payment_notes' => $order->payment_notes,
                'payment_negotiation_enabled' => $order->payment_negotiation_enabled,
                'created_at' => $order->created_at,
                'verified_at' => $order->verified_at,
                'preparing_at' => $order->preparing_at,
                'ready_at' => $order->ready_at,
                'delivered_at' => $order->delivered_at,
                'online_store' => [
                    'id' => $order->onlineStore->id,
                    'name' => $order->onlineStore->name,
                    'domain' => $order->onlineStore->domain,
                ]
            ]
        ]);
    }
}