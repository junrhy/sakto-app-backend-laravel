<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RetailItem;
use App\Models\RetailCategory;
use App\Models\RetailStockTransaction;
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
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        $data = $request->all();
        
        // Handle image uploads if present
        if ($request->hasFile('images')) {
            $imageUrls = [];
            foreach ($request->file('images') as $image) {
                $imageUrl = Storage::disk('s3')->put('inventory-images', $image);
                $imageUrls[] = Storage::disk('s3')->url($imageUrl);
            }
            $data['images'] = $imageUrls;
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
        $inventory = RetailItem::find($request->id);
        $inventory->update($request->all());
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
}

