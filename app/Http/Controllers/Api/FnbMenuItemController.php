<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbMenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FnbMenuItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->client_identifier;

        $fnbMenuItems = FnbMenuItem::where('client_identifier', $clientIdentifier)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'category' => $item->category,
                'image' => $item->image,
                'is_available_personal' => $item->is_available_personal,
                'is_available_online' => $item->is_available_online,
                'delivery_fee' => $item->delivery_fee,
                'client_identifier' => $item->client_identifier
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'FNB Menu Items retrieved successfully',
            'data' => [
                'fnb_menu_items' => $fnbMenuItems
            ]
        ]);
    }

    public function show(Request $request)
    {
        $fnbMenuItem = FnbMenuItem::find($request->id);
        return response()->json([
            'status' => 'success',
            'message' => 'FNB Menu Item retrieved successfully',
            'data' => [
                'fnb_menu_item' => $fnbMenuItem
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string',
            'image' => 'nullable|string',
            'client_identifier' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0'
        ]);
    
        return FnbMenuItem::create($validated);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string',
            'image' => 'nullable|string',
            'client_identifier' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0'
        ]);

        $fnbMenuItem = FnbMenuItem::find($request->id);

        $fnbMenuItem->update($validated);
        return response()->json(['status' => 'success', 'message' => 'Menu item updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $fnbMenuItem = FnbMenuItem::find($request->id);
        $fnbMenuItem->delete();
        return response()->noContent();
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:fnb_menu_items,id'
        ]);

        FnbMenuItem::whereIn('id', $validated['ids'])->delete();
        return response()->noContent();
    }
}
