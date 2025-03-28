<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RetailItem;
use App\Models\RetailCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
}
