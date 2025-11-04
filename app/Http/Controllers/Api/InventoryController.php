<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RetailItem;
use App\Models\RetailCategory;
use App\Models\RetailStockTransaction;
use App\Models\RetailItemVariant;
use App\Models\RetailDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $inventories = [
            'products' => RetailItem::where('client_identifier', $clientIdentifier)->get(),
            'categories' => RetailCategory::where('client_identifier', $clientIdentifier)->get(),
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Inventories retrieved successfully',
            'data' => $inventories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255',
            'client_identifier' => 'required',
            'images' => 'nullable|array',
            'images.*' => 'nullable|string|url' // Images are now URLs from frontend
        ]);

        $data = $request->all();
        
        // Handle images array - ensure it's properly formatted
        if (isset($data['images'])) {
            // If images is a JSON string, decode it
            if (is_string($data['images'])) {
                $decoded = json_decode($data['images'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data['images'] = $decoded;
                }
            }
            
            // Ensure it's an array
            if (!is_array($data['images'])) {
                $data['images'] = [];
            } else {
                // Filter out any nested arrays, empty values, and keep only valid URL strings
                $data['images'] = array_values(array_filter($data['images'], function($item) {
                    return is_string($item) && !empty($item) && filter_var($item, FILTER_VALIDATE_URL);
                }));
            }
        } else {
            $data['images'] = [];
        }
        
        $inventory = RetailItem::create($data);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Inventory created successfully',
            'data' => $inventory
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $inventory = RetailItem::find($request->id);
        return response()->json([
            'status' => 'success',
            'message' => 'Inventory retrieved successfully',
            'data' => $inventory
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        $inventory = RetailItem::find($request->id);
        return response()->json([
            'status' => 'success',
            'message' => 'Inventory retrieved successfully',
            'data' => $inventory
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'sometimes|string|max:255',
            'sku' => 'sometimes|string|max:255',
            'images' => 'nullable|array',
            'images.*' => 'nullable|string|url' // Images are now URLs from frontend
        ]);

        $inventory = RetailItem::find($request->id);
        
        if (!$inventory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }

        $data = $request->all();
        
        // Handle images array - ensure it's properly formatted
        if (isset($data['images'])) {
            // If images is a JSON string, decode it
            if (is_string($data['images'])) {
                $decoded = json_decode($data['images'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data['images'] = $decoded;
                }
            }
            
            // Ensure it's an array
            if (!is_array($data['images'])) {
                $data['images'] = [];
            } else {
                // Filter out any nested arrays, empty values, and keep only valid URL strings
                $data['images'] = array_values(array_filter($data['images'], function($item) {
                    return is_string($item) && !empty($item) && filter_var($item, FILTER_VALIDATE_URL);
                }));
            }
        } else {
            // If images not provided, preserve existing ones
            unset($data['images']);
        }
        
        $inventory->update($data);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Inventory updated successfully',
            'data' => $inventory
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $inventory = RetailItem::find($request->id);
        $inventory->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Inventory deleted successfully'
        ]);
    }

    /**
     * Remove multiple resources from storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:retail_items,id'
        ]);

        RetailItem::whereIn('id', $request->ids)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Selected inventories deleted successfully'
        ]);
    }

    public function bulkOperation(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:retail_items,id',
            'operation' => 'required|in:price,category,stock',
            'client_identifier' => 'required|string',
        ]);

        $clientIdentifier = $request->client_identifier;
        $items = RetailItem::where('client_identifier', $clientIdentifier)
            ->whereIn('id', $request->ids)
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No items found to update'
            ], 404);
        }

        DB::beginTransaction();
        try {
            if ($request->operation === 'price') {
                $request->validate([
                    'price_type' => 'required|in:percentage,fixed',
                    'price_value' => 'required|numeric',
                ]);

                $priceType = $request->price_type;
                $priceValue = $request->price_value;

                foreach ($items as $item) {
                    if ($priceType === 'percentage') {
                        $item->price = $item->price * (1 + ($priceValue / 100));
                    } else {
                        $item->price = max(0, $item->price + $priceValue);
                    }
                    $item->save();
                }

            } elseif ($request->operation === 'category') {
                $request->validate([
                    'category_id' => 'required|integer',
                ]);

                $categoryId = $request->category_id;
                foreach ($items as $item) {
                    $item->category_id = $categoryId === 0 ? null : $categoryId;
                    $item->save();
                }

            } elseif ($request->operation === 'stock') {
                $request->validate([
                    'stock_action' => 'required|in:add,remove,set',
                    'stock_value' => 'required|integer|min:1',
                ]);

                $stockAction = $request->stock_action;
                $stockValue = $request->stock_value;

                foreach ($items as $item) {
                    $previousQuantity = $item->quantity;

                    if ($stockAction === 'add') {
                        $item->quantity += $stockValue;
                    } elseif ($stockAction === 'remove') {
                        $item->quantity = max(0, $item->quantity - $stockValue);
                    } else { // set
                        $item->quantity = $stockValue;
                    }

                    $newQuantity = $item->quantity;
                    $item->save();

                    // Record stock transaction
                    RetailStockTransaction::create([
                        'client_identifier' => $clientIdentifier,
                        'retail_item_id' => $item->id,
                        'transaction_type' => $stockAction === 'set' ? 'adjustment' : ($stockAction === 'add' ? 'add' : 'remove'),
                        'quantity' => $stockValue,
                        'previous_quantity' => $previousQuantity,
                        'new_quantity' => $newQuantity,
                        'reason' => 'Bulk stock operation',
                        'performed_by' => $request->input('performed_by', 'System'),
                        'transaction_date' => now(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bulk operation completed successfully',
                'data' => [
                    'affected_items' => $items->count()
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to perform bulk operation: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getProductsOverview()
    {
        $products = RetailItem::all();

        $inventory = [
            'products' => $products,
            'categories' => RetailCategory::all(),
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Products overview retrieved successfully',
            'data' => $inventory
        ]);
    }

    public function uploadImages(Request $request)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        $images = $request->file('images');
        $imageUrls = [];

        foreach ($images as $image) {
            $imageUrl = Storage::disk('s3')->put('inventory-images', $image);
            $imageUrls[] = $imageUrl;
        }

        return response()->json(['urls' => $imageUrls]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_identifier' => 'required|string',
        ]);

        $category = RetailCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'client_identifier' => $request->client_identifier,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Update the specified category in storage.
     */
    public function updateCategory(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = RetailCategory::where('client_identifier', $request->client_identifier)
            ->findOrFail($id);
        
        $category->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroyCategory(Request $request, $id)
    {
        $category = RetailCategory::where('client_identifier', $request->client_identifier)
            ->findOrFail($id);

        // Check if category has items
        if ($category->items()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete category with existing products'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Category deleted successfully'
        ]);
    }

    /**
     * Add stock to an inventory item
     */
    public function addStock(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        $item = RetailItem::where('client_identifier', $clientIdentifier)->findOrFail($id);

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
            'reference_number' => 'nullable|string|max:255',
            'performed_by' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($item, $request, $clientIdentifier) {
            $quantity = $request->quantity;
            $previousQuantity = $item->quantity;
            $newQuantity = $previousQuantity + $quantity;

            // Create transaction record
            RetailStockTransaction::create([
                'client_identifier' => $clientIdentifier,
                'retail_item_id' => $item->id,
                'transaction_type' => 'add',
                'quantity' => $quantity,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'reason' => $request->reason,
                'reference_number' => $request->reference_number,
                'performed_by' => $request->performed_by,
                'transaction_date' => now(),
            ]);

            // Update stock
            $item->increment('quantity', $quantity);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Stock added successfully',
            'data' => $item->fresh()
        ]);
    }

    /**
     * Remove stock from an inventory item
     */
    public function removeStock(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        $item = RetailItem::where('client_identifier', $clientIdentifier)->findOrFail($id);

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
            'reference_number' => 'nullable|string|max:255',
            'performed_by' => 'nullable|string|max:255',
        ]);

        if ($item->quantity < $request->quantity) {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient stock. Available: ' . $item->quantity
            ], 400);
        }

        DB::transaction(function () use ($item, $request, $clientIdentifier) {
            $quantity = $request->quantity;
            $previousQuantity = $item->quantity;
            $newQuantity = $previousQuantity - $quantity;

            // Create transaction record
            RetailStockTransaction::create([
                'client_identifier' => $clientIdentifier,
                'retail_item_id' => $item->id,
                'transaction_type' => 'remove',
                'quantity' => $quantity,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'reason' => $request->reason,
                'reference_number' => $request->reference_number,
                'performed_by' => $request->performed_by,
                'transaction_date' => now(),
            ]);

            // Update stock
            $item->decrement('quantity', $quantity);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Stock removed successfully',
            'data' => $item->fresh()
        ]);
    }

    /**
     * Adjust stock (for corrections)
     */
    public function adjustStock(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        $item = RetailItem::where('client_identifier', $clientIdentifier)->findOrFail($id);

        $request->validate([
            'new_quantity' => 'required|integer|min:0',
            'reason' => 'required|string|max:500',
            'reference_number' => 'nullable|string|max:255',
            'performed_by' => 'nullable|string|max:255',
        ]);

        $oldQuantity = $item->quantity;
        $newQuantity = $request->new_quantity;
        $difference = abs($newQuantity - $oldQuantity);

        DB::transaction(function () use ($item, $request, $clientIdentifier, $oldQuantity, $newQuantity, $difference) {
            // Create transaction record
            RetailStockTransaction::create([
                'client_identifier' => $clientIdentifier,
                'retail_item_id' => $item->id,
                'transaction_type' => 'adjustment',
                'quantity' => $difference,
                'previous_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'reason' => $request->reason,
                'reference_number' => $request->reference_number,
                'performed_by' => $request->performed_by,
                'transaction_date' => now(),
            ]);

            // Update stock
            $item->update(['quantity' => $newQuantity]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Stock adjusted successfully',
            'data' => $item->fresh()
        ]);
    }

    /**
     * Get transaction history for an item
     */
    public function getStockHistory(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        
        $item = RetailItem::where('client_identifier', $clientIdentifier)->findOrFail($id);
        
        $transactions = RetailStockTransaction::forClient($clientIdentifier)
            ->where('retail_item_id', $id)
            ->orderBy('transaction_date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Stock history retrieved successfully',
            'data' => [
                'item' => $item,
                'transactions' => $transactions
            ]
        ]);
    }

    /**
     * Get low stock alerts
     */
    public function getLowStockAlerts(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $threshold = $request->input('threshold', null); // Optional custom threshold
        
        $query = RetailItem::where('client_identifier', $clientIdentifier);
        
        if ($threshold !== null) {
            // Use custom threshold if provided
            $lowStockItems = $query->whereRaw('quantity <= ?', [$threshold])->get();
        } else {
            // Use item's own threshold or default to 10
            $lowStockItems = $query->whereRaw('quantity <= COALESCE(low_stock_threshold, 10)')->get();
        }
        
        // Add status information
        $lowStockItems = $lowStockItems->map(function($item) {
            $item->status = $item->quantity === 0 ? 'out_of_stock' : 'low_stock';
            $item->threshold = $item->low_stock_threshold ?? 10;
            return $item;
        });
        
        return response()->json([
            'status' => 'success',
            'message' => 'Low stock alerts retrieved successfully',
            'data' => [
                'items' => $lowStockItems,
                'count' => $lowStockItems->count()
            ]
        ]);
    }

    // Variant Management Methods
    public function getVariants(Request $request, $itemId)
    {
        $clientIdentifier = $request->client_identifier;
        $variants = RetailItemVariant::where('client_identifier', $clientIdentifier)
            ->where('retail_item_id', $itemId)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Variants retrieved successfully',
            'data' => $variants
        ]);
    }

    public function storeVariant(Request $request, $itemId)
    {
        $request->validate([
            'sku' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'attributes' => 'required|array',
            'image' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $variant = RetailItemVariant::create([
            'retail_item_id' => $itemId,
            'client_identifier' => $request->client_identifier,
            'sku' => $request->sku,
            'barcode' => $request->barcode,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'attributes' => $request->attributes,
            'image' => $request->image,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Variant created successfully',
            'data' => $variant
        ], 201);
    }

    public function updateVariant(Request $request, $itemId, $variantId)
    {
        $request->validate([
            'sku' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'quantity' => 'integer|min:0',
            'attributes' => 'array',
            'image' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $variant = RetailItemVariant::where('client_identifier', $request->client_identifier)
            ->where('retail_item_id', $itemId)
            ->findOrFail($variantId);

        $variant->update($request->only([
            'sku', 'barcode', 'price', 'quantity', 'attributes', 'image', 'is_active'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Variant updated successfully',
            'data' => $variant
        ]);
    }

    public function destroyVariant(Request $request, $itemId, $variantId)
    {
        $variant = RetailItemVariant::where('client_identifier', $request->client_identifier)
            ->where('retail_item_id', $itemId)
            ->findOrFail($variantId);

        $variant->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Variant deleted successfully'
        ]);
    }

    // Discount Management Methods
    public function getDiscounts(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $discounts = RetailDiscount::where('client_identifier', $clientIdentifier)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Discounts retrieved successfully',
            'data' => $discounts
        ]);
    }

    public function getActiveDiscounts(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $discounts = RetailDiscount::forClient($clientIdentifier)
            ->valid()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Active discounts retrieved successfully',
            'data' => $discounts
        ]);
    }

    public function storeDiscount(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,buy_x_get_y',
            'value' => 'required|numeric|min:0',
            'min_quantity' => 'nullable|integer|min:1',
            'buy_quantity' => 'nullable|integer|min:1',
            'get_quantity' => 'nullable|integer|min:1',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'applicable_items' => 'nullable|array',
            'applicable_categories' => 'nullable|array',
            'usage_limit' => 'nullable|integer|min:1',
        ]);

        $discount = RetailDiscount::create([
            'client_identifier' => $request->client_identifier,
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'value' => $request->value,
            'min_quantity' => $request->min_quantity,
            'buy_quantity' => $request->buy_quantity,
            'get_quantity' => $request->get_quantity,
            'min_purchase_amount' => $request->min_purchase_amount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $request->is_active ?? true,
            'applicable_items' => $request->applicable_items,
            'applicable_categories' => $request->applicable_categories,
            'usage_limit' => $request->usage_limit,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Discount created successfully',
            'data' => $discount
        ], 201);
    }

    public function updateDiscount(Request $request, $id)
    {
        $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'type' => 'in:percentage,fixed,buy_x_get_y',
            'value' => 'numeric|min:0',
            'min_quantity' => 'nullable|integer|min:1',
            'buy_quantity' => 'nullable|integer|min:1',
            'get_quantity' => 'nullable|integer|min:1',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'applicable_items' => 'nullable|array',
            'applicable_categories' => 'nullable|array',
            'usage_limit' => 'nullable|integer|min:1',
        ]);

        $discount = RetailDiscount::where('client_identifier', $request->client_identifier)
            ->findOrFail($id);

        $discount->update($request->only([
            'name', 'description', 'type', 'value', 'min_quantity',
            'buy_quantity', 'get_quantity', 'min_purchase_amount',
            'start_date', 'end_date', 'is_active', 'applicable_items',
            'applicable_categories', 'usage_limit'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Discount updated successfully',
            'data' => $discount
        ]);
    }

    public function destroyDiscount(Request $request, $id)
    {
        $discount = RetailDiscount::where('client_identifier', $request->client_identifier)
            ->findOrFail($id);

        $discount->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Discount deleted successfully'
        ]);
    }

    public function calculateDiscount(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
            'category_id' => 'nullable|integer',
            'quantity' => 'required|integer|min:1',
            'item_price' => 'required|numeric|min:0',
            'purchase_amount' => 'nullable|numeric|min:0',
        ]);

        $clientIdentifier = $request->client_identifier;
        $discounts = RetailDiscount::forClient($clientIdentifier)
            ->valid()
            ->get();

        $bestDiscount = null;
        $maxDiscountAmount = 0;

        foreach ($discounts as $discount) {
            if ($discount->isApplicableToItem(
                $request->item_id,
                $request->category_id,
                $request->quantity,
                $request->purchase_amount ?? ($request->item_price * $request->quantity)
            )) {
                $discountAmount = $discount->calculateDiscount(
                    $request->item_price,
                    $request->quantity
                );
                if ($discountAmount > $maxDiscountAmount) {
                    $maxDiscountAmount = $discountAmount;
                    $bestDiscount = $discount;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Discount calculated successfully',
            'data' => [
                'discount' => $bestDiscount,
                'discount_amount' => $maxDiscountAmount,
                'final_price' => ($request->item_price * $request->quantity) - $maxDiscountAmount
            ]
        ]);
    }
}

