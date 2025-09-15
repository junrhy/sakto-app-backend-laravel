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
        
        // Use caching for frequently accessed menu items
        $cacheKey = "fnb_menu_items_{$clientIdentifier}";
        
        $fnbMenuItems = cache()->remember($cacheKey, 600, function () use ($clientIdentifier) { // Cache for 10 minutes
            return FnbMenuItem::where('client_identifier', $clientIdentifier)
                ->select('id', 'name', 'price', 'category', 'image', 'is_available_personal', 'is_available_online', 'delivery_fee', 'client_identifier')
                ->orderBy('category')
                ->orderBy('name')
                ->get()
                ->map(function ($item) {
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
    
        $menuItem = FnbMenuItem::create($validated);
        
        // Clear cache when menu items are modified
        if ($validated['client_identifier']) {
            cache()->forget("fnb_menu_items_{$validated['client_identifier']}");
            cache()->forget('fnb_restaurants_' . md5('all_restaurants'));
        }
        
        return $menuItem;
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
        
        // Clear cache when menu items are modified
        if ($fnbMenuItem->client_identifier) {
            cache()->forget("fnb_menu_items_{$fnbMenuItem->client_identifier}");
            cache()->forget('fnb_restaurants_' . md5('all_restaurants'));
        }
        
        return response()->json(['status' => 'success', 'message' => 'Menu item updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $fnbMenuItem = FnbMenuItem::find($request->id);
        $clientIdentifier = $fnbMenuItem->client_identifier;
        $fnbMenuItem->delete();
        
        // Clear cache when menu items are deleted
        if ($clientIdentifier) {
            cache()->forget("fnb_menu_items_{$clientIdentifier}");
            cache()->forget('fnb_restaurants_' . md5('all_restaurants'));
        }
        
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
