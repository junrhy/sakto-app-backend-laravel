<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryCategory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inventories = Inventory::all();
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
        $categories = InventoryCategory::all();
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
        $inventory = Inventory::create($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Inventory created successfully',
            'data' => $inventory
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Inventory $inventory)
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
    public function edit(Inventory $inventory)
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
    public function update(Request $request, Inventory $inventory)
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
    public function destroy(Inventory $inventory)
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

        Inventory::whereIn('id', $request->ids)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Selected inventories deleted successfully'
        ]);
    }
}
