<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodDeliveryOrder;
use App\Models\FoodDeliveryOrderItem;
use App\Models\FoodDeliveryRestaurant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FoodDeliveryOrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        $query = FoodDeliveryOrder::where('client_identifier', $clientIdentifier)
            ->with(['restaurant', 'driver', 'orderItems.menuItem']);

        // Filter by status
        if ($request->has('order_status')) {
            $query->where('order_status', $request->order_status);
        }

        // Filter by restaurant
        if ($request->has('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        // Filter by driver
        if ($request->has('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_reference', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'customer_id' => 'nullable|integer',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_address' => 'required|string',
            'customer_coordinates' => 'nullable|string',
            'restaurant_id' => 'required|exists:food_delivery_restaurants,id',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'nullable|exists:food_delivery_menu_items,id',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.item_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.special_instructions' => 'nullable|string',
            'subtotal' => 'required|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
            'service_charge' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => ['required', Rule::in(['online', 'cash_on_delivery'])],
            'special_instructions' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $items = $validated['items'];
        unset($validated['items']);

        // Get restaurant for delivery fee and minimum order
        $restaurant = FoodDeliveryRestaurant::find($validated['restaurant_id']);
        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found'
            ], 404);
        }

        // Set delivery fee if not provided
        if (!isset($validated['delivery_fee'])) {
            $validated['delivery_fee'] = $restaurant->delivery_fee;
        }

        // Calculate total
        $subtotal = $validated['subtotal'];
        $deliveryFee = $validated['delivery_fee'] ?? 0;
        $serviceCharge = $validated['service_charge'] ?? 0;
        $discount = $validated['discount'] ?? 0;
        $validated['total_amount'] = $subtotal + $deliveryFee + $serviceCharge - $discount;

        // Check minimum order amount
        if ($validated['total_amount'] < $restaurant->minimum_order_amount) {
            return response()->json([
                'success' => false,
                'message' => "Minimum order amount is {$restaurant->minimum_order_amount}"
            ], 400);
        }

        // Generate order reference
        $validated['order_reference'] = FoodDeliveryOrder::generateOrderReference();
        $validated['order_status'] = 'pending';
        $validated['payment_status'] = 'pending';

        // Calculate estimated delivery time
        $prepTime = $restaurant->estimated_prep_time;
        $estimatedDeliveryTime = now()->addMinutes($prepTime + 30); // Add 30 min for delivery
        $validated['estimated_delivery_time'] = $estimatedDeliveryTime;

        // Create order
        $order = FoodDeliveryOrder::create($validated);

        // Create order items
        foreach ($items as $item) {
            $item['order_id'] = $order->id;
            $item['subtotal'] = $item['item_price'] * $item['quantity'];
            FoodDeliveryOrderItem::create($item);
        }

        // Create initial tracking entry
        $order->updateStatus('pending', null, 'Order placed', 'system');

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order->load(['restaurant', 'orderItems.menuItem', 'trackings'])
        ], 201);
    }

    /**
     * Display the specified order
     */
    public function show($id, Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $order = FoodDeliveryOrder::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->with(['restaurant', 'driver', 'orderItems.menuItem', 'trackings', 'payments'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Get order by reference (public tracking)
     */
    public function getByReference($reference): JsonResponse
    {
        $order = FoodDeliveryOrder::where('order_reference', $reference)
            ->with(['restaurant', 'driver', 'orderItems.menuItem', 'trackings'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $order = FoodDeliveryOrder::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'order_status' => ['required', Rule::in([
                'pending',
                'accepted',
                'preparing',
                'ready',
                'assigned',
                'out_for_delivery',
                'delivered',
                'cancelled'
            ])],
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order->updateStatus(
            $request->order_status,
            $request->location,
            $request->notes,
            $request->input('updated_by', 'system')
        );

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order->load(['restaurant', 'driver', 'orderItems.menuItem', 'trackings'])
        ]);
    }

    /**
     * Assign driver to order
     */
    public function assignDriver(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $order = FoodDeliveryOrder::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|exists:food_delivery_drivers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($order->order_status !== 'ready') {
            return response()->json([
                'success' => false,
                'message' => 'Order must be ready before assigning driver'
            ], 400);
        }

        $order->assignDriver($request->driver_id);

        return response()->json([
            'success' => true,
            'message' => 'Driver assigned successfully',
            'data' => $order->load(['restaurant', 'driver', 'orderItems.menuItem'])
        ]);
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $order = FoodDeliveryOrder::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if (in_array($order->order_status, ['delivered', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel order with status: ' . $order->order_status
            ], 400);
        }

        $order->updateStatus('cancelled', null, $request->input('notes', 'Order cancelled'), 'system');

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully',
            'data' => $order
        ]);
    }
}
