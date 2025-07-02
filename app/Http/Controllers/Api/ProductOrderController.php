<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductOrder;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProductOrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->get('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json(['error' => 'Client identifier is required'], 400);
        }

        $query = ProductOrder::forClient($clientIdentifier);

        // Filter by contact_id if provided
        if ($request->has('contact_id') && $request->contact_id) {
            $query->where('contact_id', $request->contact_id);
        }

        // Apply filters
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('payment_status')) {
            $query->byPaymentStatus($request->payment_status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        // Apply date filters
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $orders = $query->paginate($perPage);

        // Enhance order_items with product names for each order
        $orders->getCollection()->transform(function ($order) {
            $enhancedOrderItems = collect($order->order_items)->map(function ($item) {
                $product = Product::find($item['product_id']);
                return [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'attributes' => $item['attributes'] ?? null,
                    'name' => $product ? $product->name : 'Product not found',
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ];
            })->toArray();

            $order->order_items = $enhancedOrderItems;
            return $order;
        });

        return response()->json($orders);
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request): JsonResponse
    {
        // Debug logging to see what's being received
        Log::info('Product order store request received', [
            'request_data' => $request->all(),
            'order_items' => $request->order_items,
            'order_items_count' => count($request->order_items ?? []),
            'order_items_raw' => $request->input('order_items'),
            'order_items_first_item' => $request->order_items[0] ?? null,
            'order_items_keys' => $request->order_items ? array_keys($request->order_items[0]) : [],
        ]);

        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'contact_id' => 'nullable|integer',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'shipping_address' => 'nullable|string',
            'billing_address' => 'nullable|string',
            'order_items' => 'required|array|min:1',
            'order_items.*.product_id' => 'required|integer',
            'order_items.*.name' => 'nullable|string',
            'order_items.*.variant_id' => 'nullable|integer',
            'order_items.*.quantity' => 'required|integer|min:1',
            'order_items.*.price' => 'required|numeric|min:0',
            'order_items.*.attributes' => 'nullable|array',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'shipping_fee' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed', [
                'errors' => $validator->errors(),
                'request_data' => $request->all(),
            ]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Log validated data
            $validatedData = $validator->validated();
            Log::info('Validation passed', [
                'validated_data' => $validatedData,
                'order_items_validated' => $validatedData['order_items'] ?? [],
                'order_items_keys' => array_keys($validatedData['order_items'][0] ?? []),
                'contact_id_validated' => $validatedData['contact_id'] ?? 'not_set',
                'has_contact_id_in_validated' => isset($validatedData['contact_id']),
            ]);

            // Validate stock availability
            foreach ($request->order_items as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product) {
                    return response()->json(['error' => "Product with ID {$item['product_id']} not found"], 404);
                }

                if ($product->type === 'physical' && $product->stock_quantity < $item['quantity']) {
                    return response()->json([
                        'error' => "Insufficient stock for product: {$product->name}. Available: {$product->stock_quantity}, Requested: {$item['quantity']}"
                    ], 400);
                }
            }

            // Create order
            $orderData = $validatedData;
            $orderData['order_number'] = ProductOrder::generateOrderNumber();
            $orderData['order_status'] = 'pending';
            $orderData['payment_status'] = 'pending';

            Log::info('Creating order with data', [
                'order_data' => $orderData,
                'order_items_final' => $orderData['order_items'],
                'contact_id_in_order_data' => $orderData['contact_id'] ?? 'not_set',
                'has_contact_id' => isset($orderData['contact_id']),
            ]);

            $order = ProductOrder::create($orderData);

            // Debug logging to see what was saved
            Log::info('Product order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'client_identifier' => $order->client_identifier,
                'contact_id_saved' => $order->contact_id,
                'total_amount' => $order->total_amount,
                'saved_order_items' => $order->order_items,
                'validated_order_items' => $orderData['order_items'],
            ]);

            // Update product stock for physical products
            foreach ($request->order_items as $item) {
                $product = Product::find($item['product_id']);
                if ($product->type === 'physical') {
                    $product->decrement('stock_quantity', $item['quantity']);
                }
            }

            // If payment method is provided and not COD, mark as paid
            if ($request->payment_method && $request->payment_method !== 'cod') {
                $order->markAsPaid();
            }

            return response()->json($order, 201);

        } catch (\Exception $e) {
            Log::error('Failed to create product order', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json(['error' => 'Failed to create order'], 500);
        }
    }

    /**
     * Display the specified order
     */
    public function show(string $id): JsonResponse
    {
        $order = ProductOrder::find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Enhance order_items with product names
        $enhancedOrderItems = collect($order->order_items)->map(function ($item) {
            $product = Product::find($item['product_id']);
            return [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'attributes' => $item['attributes'] ?? null,
                'name' => $product ? $product->name : 'Product not found',
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ];
        })->toArray();

        $orderData = $order->toArray();
        $orderData['order_items'] = $enhancedOrderItems;

        return response()->json($orderData);
    }

    /**
     * Update the specified order
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $order = ProductOrder::find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'order_status' => ['nullable', Rule::in(['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])],
            'payment_status' => ['nullable', Rule::in(['pending', 'paid', 'failed', 'refunded', 'partially_refunded'])],
            'payment_method' => ['nullable', Rule::in(['cash', 'card', 'bank_transfer', 'digital_wallet', 'cod'])],
            'payment_reference' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string',
            'billing_address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order->update($validator->validated());

        // Handle status-specific actions
        if ($request->has('order_status')) {
            switch ($request->order_status) {
                case 'shipped':
                    $order->markAsShipped();
                    break;
                case 'delivered':
                    $order->markAsDelivered();
                    break;
                case 'cancelled':
                    $order->cancel();
                    // Restore stock for cancelled orders
                    foreach ($order->order_items as $item) {
                        $product = Product::find($item['product_id']);
                        if ($product && $product->type === 'physical') {
                            $product->increment('stock_quantity', $item['quantity']);
                        }
                    }
                    break;
            }
        }

        if ($request->has('payment_status') && $request->payment_status === 'paid') {
            $order->markAsPaid();
        }

        return response()->json($order);
    }

    /**
     * Remove the specified order
     */
    public function destroy(string $id): JsonResponse
    {
        $order = ProductOrder::find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Only allow deletion of pending or cancelled orders
        if (!in_array($order->order_status, ['pending', 'cancelled'])) {
            return response()->json(['error' => 'Cannot delete order with current status'], 400);
        }

        // Restore stock for physical products
        foreach ($order->order_items as $item) {
            $product = Product::find($item['product_id']);
            if ($product && $product->type === 'physical') {
                $product->increment('stock_quantity', $item['quantity']);
            }
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }

    /**
     * Get order statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $clientIdentifier = $request->get('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json(['error' => 'Client identifier is required'], 400);
        }

        $query = ProductOrder::forClient($clientIdentifier);

        // Date range filter
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());
        
        $query->whereBetween('created_at', [$dateFrom, $dateTo]);

        $statistics = [
            'total_orders' => $query->count(),
            'total_revenue' => $query->sum('total_amount'),
            'pending_orders' => $query->clone()->byStatus('pending')->count(),
            'processing_orders' => $query->clone()->byStatus('processing')->count(),
            'shipped_orders' => $query->clone()->byStatus('shipped')->count(),
            'delivered_orders' => $query->clone()->byStatus('delivered')->count(),
            'cancelled_orders' => $query->clone()->byStatus('cancelled')->count(),
            'paid_orders' => $query->clone()->byPaymentStatus('paid')->count(),
            'pending_payments' => $query->clone()->byPaymentStatus('pending')->count(),
            'average_order_value' => $query->avg('total_amount') ?? 0,
        ];

        return response()->json($statistics);
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders(Request $request): JsonResponse
    {
        $clientIdentifier = $request->get('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json(['error' => 'Client identifier is required'], 400);
        }

        $limit = $request->get('limit', 10);
        $orders = ProductOrder::forClient($clientIdentifier)
            ->recent(30)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($orders);
    }

    /**
     * Process payment for an order
     */
    public function processPayment(Request $request, string $id): JsonResponse
    {
        $order = ProductOrder::find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => ['required', Rule::in(['cash', 'card', 'bank_transfer', 'digital_wallet'])],
            'payment_reference' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order->update([
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
        ]);

        $order->markAsPaid();

        return response()->json([
            'message' => 'Payment processed successfully',
            'order' => $order,
        ]);
    }
} 