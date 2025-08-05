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
                    'status' => $item['status'] ?? 'pending',
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
            'order_items.*.status' => 'nullable|string|in:pending,confirmed,processing,shipped,delivered,cancelled,out_of_stock',
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

            // Validate stock availability and set item statuses
            $processedOrderItems = [];
            foreach ($request->order_items as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product) {
                    return response()->json(['error' => "Product with ID {$item['product_id']} not found"], 404);
                }

                // Set item status based on stock availability
                $itemStatus = 'pending'; // Default status for all items initially
                
                if ($product->type === 'physical') {
                    if ($product->stock_quantity < $item['quantity']) {
                        return response()->json([
                            'error' => "Insufficient stock for product: {$product->name}. Available: {$product->stock_quantity}, Requested: {$item['quantity']}"
                        ], 400);
                    }
                    // Keep as 'pending' - stock will be reserved when order is confirmed
                } else {
                    // Digital, service, or subscription products can be confirmed immediately
                    $itemStatus = 'confirmed';
                }

                // Add status to the item
                $processedItem = $item;
                $processedItem['status'] = $itemStatus;
                $processedOrderItems[] = $processedItem;
            }

            // Create order
            $orderData = $validatedData;
            $orderData['order_items'] = $processedOrderItems; // Use processed items with statuses
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

            // No stock adjustment needed for initial pending state

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

            // Stock will be adjusted automatically based on order status changes
            // No manual stock adjustment needed here

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
                'status' => $item['status'] ?? 'pending',
            ];
        })->toArray();

        $orderData = $order->toArray();
        $orderData['order_items'] = $enhancedOrderItems;

        // Add stock availability data
        $orderData['stock_availability'] = $order->getStockAvailability();
        $orderData['stock_summary'] = $order->getStockStatusSummary();

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
            'order_items' => 'nullable|array',
            'order_items.*.product_id' => 'required|integer',
            'order_items.*.name' => 'nullable|string',
            'order_items.*.variant_id' => 'nullable|integer',
            'order_items.*.quantity' => 'required|integer|min:1',
            'order_items.*.price' => 'required|numeric|min:0',
            'order_items.*.attributes' => 'nullable|array',
            'order_items.*.status' => 'nullable|string|in:pending,confirmed,processing,shipped,delivered,cancelled,out_of_stock',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        
        // Update order items if provided
        if (isset($validatedData['order_items'])) {
            $order->order_items = $validatedData['order_items'];
            unset($validatedData['order_items']); // Remove from main update data
        }
        
        $order->update($validatedData);

        // Handle status-specific actions with automatic stock adjustment
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
                    break;
                case 'confirmed':
                    $order->updateOrderStatus('confirmed');
                    break;
                case 'processing':
                    $order->updateOrderStatus('processing');
                    break;
                case 'refunded':
                    $order->updateOrderStatus('refunded');
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

        // Stock restoration is handled automatically by the model
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

        // Basic statistics
        $totalOrders = $query->count();
        $totalRevenue = $query->sum('total_amount');
        $averageOrderValue = $query->avg('total_amount') ?? 0;

        // Orders by status
        $ordersByStatus = [
            'pending' => $query->clone()->byStatus('pending')->count(),
            'confirmed' => $query->clone()->byStatus('confirmed')->count(),
            'processing' => $query->clone()->byStatus('processing')->count(),
            'shipped' => $query->clone()->byStatus('shipped')->count(),
            'delivered' => $query->clone()->byStatus('delivered')->count(),
            'cancelled' => $query->clone()->byStatus('cancelled')->count(),
            'refunded' => $query->clone()->byStatus('refunded')->count(),
        ];

        // Orders by payment status
        $ordersByPaymentStatus = [
            'pending' => $query->clone()->byPaymentStatus('pending')->count(),
            'paid' => $query->clone()->byPaymentStatus('paid')->count(),
            'failed' => $query->clone()->byPaymentStatus('failed')->count(),
            'refunded' => $query->clone()->byPaymentStatus('refunded')->count(),
            'partially_refunded' => $query->clone()->byPaymentStatus('partially_refunded')->count(),
        ];

        // Revenue by month (last 12 months)
        $revenueByMonth = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthRevenue = ProductOrder::forClient($clientIdentifier)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('total_amount');
            $monthOrders = ProductOrder::forClient($clientIdentifier)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            
            $revenueByMonth[] = [
                'month' => $month->format('M Y'),
                'revenue' => $monthRevenue,
                'orders' => $monthOrders,
            ];
        }

        // Top products (by revenue)
        $topProducts = [];
        $orderItems = ProductOrder::forClient($clientIdentifier)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->get()
            ->flatMap(function ($order) {
                return collect($order->order_items);
            })
            ->groupBy('product_id')
            ->map(function ($items, $productId) {
                $product = Product::find($productId);
                $quantitySold = $items->sum('quantity');
                $revenue = $items->sum(function ($item) {
                    return $item['quantity'] * $item['price'];
                });
                
                return [
                    'product_id' => (int) $productId,
                    'name' => $product ? $product->name : 'Unknown Product',
                    'quantity_sold' => $quantitySold,
                    'revenue' => $revenue,
                ];
            })
            ->sortByDesc('revenue')
            ->take(10)
            ->values()
            ->toArray();

        // Recent orders
        $recentOrders = ProductOrder::forClient($clientIdentifier)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'total_amount' => $order->total_amount,
                    'order_status' => $order->order_status,
                    'created_at' => $order->created_at->toISOString(),
                ];
            })
            ->toArray();

        $statistics = [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'average_order_value' => $averageOrderValue,
            'orders_by_status' => $ordersByStatus,
            'orders_by_payment_status' => $ordersByPaymentStatus,
            'revenue_by_month' => $revenueByMonth,
            'top_products' => $topProducts,
            'recent_orders' => $recentOrders,
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
     * Get orders for a specific product
     */
    public function getOrdersForProduct(Request $request, string $productId): JsonResponse
    {
        $clientIdentifier = $request->get('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json(['error' => 'Client identifier is required'], 400);
        }

        $query = ProductOrder::forClient($clientIdentifier);

        // Filter orders that contain the specific product
        $query->whereJsonContains('order_items', ['product_id' => (int) $productId]);

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

        // Enhance order_items to highlight the specific product
        $orders->getCollection()->transform(function ($order) use ($productId) {
            $enhancedOrderItems = collect($order->order_items)->map(function ($item) use ($productId) {
                $product = Product::find($item['product_id']);
                $isTargetProduct = $item['product_id'] == $productId;
                
                return [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'attributes' => $item['attributes'] ?? null,
                    'name' => $product ? $product->name : 'Product not found',
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'status' => $item['status'] ?? 'pending',
                    'is_target_product' => $isTargetProduct,
                ];
            })->toArray();

            $order->order_items = $enhancedOrderItems;
            return $order;
        });

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

    /**
     * Update status for a specific order item
     */
    public function updateItemStatus(Request $request, string $orderId, string $productId): JsonResponse
    {
        $order = ProductOrder::find($orderId);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'out_of_stock'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if the product exists in the order
        $itemExists = collect($order->order_items)->contains('product_id', (int) $productId);
        if (!$itemExists) {
            return response()->json(['error' => 'Product not found in order'], 404);
        }

        $order->updateItemStatus((int) $productId, $request->status);

        return response()->json([
            'message' => 'Item status updated successfully',
            'order' => $order->fresh(),
        ]);
    }

    /**
     * Get stock availability for an order
     */
    public function getStockAvailability(string $id): JsonResponse
    {
        $order = ProductOrder::find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $availability = $order->getStockAvailability();
        $hasAvailableStock = $order->hasAvailableStock();
        $stockSummary = $order->getStockStatusSummary();

        return response()->json([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'has_available_stock' => $hasAvailableStock,
            'stock_availability' => $availability,
            'stock_summary' => $stockSummary,
        ]);
    }

    /**
     * Confirm order and reserve stock
     */
    public function confirmOrder(string $id): JsonResponse
    {
        $order = ProductOrder::find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Check if stock is available before confirming
        if (!$order->hasAvailableStock()) {
            return response()->json([
                'error' => 'Insufficient stock to confirm order',
                'stock_availability' => $order->getStockAvailability(),
            ], 400);
        }

        $order->updateOrderStatus('confirmed');

        return response()->json([
            'message' => 'Order confirmed and stock reserved',
            'order' => $order->fresh(),
        ]);
    }

    /**
     * Cancel order and restore stock
     */
    public function cancelOrder(string $id): JsonResponse
    {
        $order = ProductOrder::find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if (!$order->canBeCancelled()) {
            return response()->json(['error' => 'Order cannot be cancelled in its current status'], 400);
        }

        $order->cancel();

        return response()->json([
            'message' => 'Order cancelled and stock restored',
            'order' => $order->fresh(),
        ]);
    }
} 