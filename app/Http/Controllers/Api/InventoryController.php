<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RetailItem;
use App\Models\RetailCategory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inventories = [
            'products' => RetailItem::all(),
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Inventories retrieved successfully',
            'data' => $inventories
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = RetailCategory::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Categories retrieved successfully',
            'data' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $inventory = RetailItem::create($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Inventory created successfully',
            'data' => $inventory
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(RetailItem $inventory)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Inventory retrieved successfully',
            'data' => $inventory
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RetailItem $inventory)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Inventory retrieved successfully',
            'data' => $inventory
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RetailItem $inventory)
    {
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
    public function destroy(RetailItem $inventory)
    {
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
            'ids.*' => 'exists:inventories,id'
        ]);

        RetailßItem::whereIn('id', $request->ids)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Selected inventories deleted successfully'
        ]);
    }
}
