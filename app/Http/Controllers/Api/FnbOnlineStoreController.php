<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbOnlineStore;
use App\Models\FnbMenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FnbOnlineStoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        
        // Use caching for frequently accessed online stores
        $cacheKey = "fnb_online_stores_{$clientIdentifier}";
        
        $onlineStores = cache()->remember($cacheKey, 600, function () use ($clientIdentifier) { // Cache for 10 minutes
            return FnbOnlineStore::where('client_identifier', $clientIdentifier)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($store) {
                    return [
                        'id' => $store->id,
                        'name' => $store->name,
                        'description' => $store->description,
                        'domain' => $store->domain,
                        'is_active' => $store->is_active,
                        'menu_items' => $store->menu_items ?? [],
                        'settings' => $store->settings ?? [],
                        'verification_required' => $store->verification_required,
                        'payment_negotiation_enabled' => $store->payment_negotiation_enabled,
                        'created_at' => $store->created_at,
                        'updated_at' => $store->updated_at,
                    ];
                });
        });

        return response()->json([
            'status' => 'success',
            'message' => 'FNB Online Stores retrieved successfully',
            'data' => [
                'fnb_online_stores' => $onlineStores
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
            'description' => 'nullable|string',
            'domain' => 'required|string|unique:fnb_online_stores,domain',
            'is_active' => 'boolean',
            'menu_items' => 'nullable|array',
            'menu_items.*' => 'integer|exists:fnb_menu_items,id',
            'settings' => 'nullable|array',
            'verification_required' => 'nullable|in:auto,manual,none',
            'payment_negotiation_enabled' => 'boolean',
            'client_identifier' => 'required|string'
        ]);

        $onlineStore = FnbOnlineStore::create($validated);
        
        // Clear cache when online stores are modified
        if ($validated['client_identifier']) {
            cache()->forget("fnb_online_stores_{$validated['client_identifier']}");
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Online store created successfully',
            'data' => [
                'fnb_online_store' => $onlineStore
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        
        $onlineStore = FnbOnlineStore::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$onlineStore) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online store not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'FNB Online Store retrieved successfully',
            'data' => [
                'fnb_online_store' => $onlineStore
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        
        $onlineStore = FnbOnlineStore::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$onlineStore) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online store not found'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'domain' => 'sometimes|string|unique:fnb_online_stores,domain,' . $id,
            'is_active' => 'boolean',
            'menu_items' => 'nullable|array',
            'menu_items.*' => 'integer|exists:fnb_menu_items,id',
            'settings' => 'nullable|array',
            'verification_required' => 'nullable|in:auto,manual,none',
            'payment_negotiation_enabled' => 'boolean',
        ]);

        $onlineStore->update($validated);
        
        // Clear cache when online stores are modified
        cache()->forget("fnb_online_stores_{$clientIdentifier}");
        
        return response()->json([
            'status' => 'success',
            'message' => 'Online store updated successfully',
            'data' => [
                'fnb_online_store' => $onlineStore
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        
        $onlineStore = FnbOnlineStore::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$onlineStore) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online store not found'
            ], 404);
        }

        $onlineStore->delete();
        
        // Clear cache when online stores are deleted
        cache()->forget("fnb_online_stores_{$clientIdentifier}");
        
        return response()->noContent();
    }

    /**
     * Toggle online store status
     */
    public function toggleStatus(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        
        $onlineStore = FnbOnlineStore::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$onlineStore) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online store not found'
            ], 404);
        }

        $validated = $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $onlineStore->update(['is_active' => $validated['is_active']]);
        
        // Clear cache when online stores are modified
        cache()->forget("fnb_online_stores_{$clientIdentifier}");
        
        return response()->json([
            'status' => 'success',
            'message' => 'Online store status updated successfully',
            'data' => [
                'id' => $onlineStore->id,
                'name' => $onlineStore->name,
                'is_active' => $onlineStore->is_active
            ]
        ]);
    }

    /**
     * Update menu items for online store
     */
    public function updateMenuItems(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        
        $onlineStore = FnbOnlineStore::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$onlineStore) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online store not found'
            ], 404);
        }

        $validated = $request->validate([
            'menu_items' => 'required|array',
            'menu_items.*' => 'integer|exists:fnb_menu_items,id'
        ]);

        // Verify all menu items belong to the client and are available online
        $validMenuItems = FnbMenuItem::whereIn('id', $validated['menu_items'])
            ->where('client_identifier', $clientIdentifier)
            ->where('is_available_online', true)
            ->pluck('id')
            ->toArray();

        if (count($validMenuItems) !== count($validated['menu_items'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some menu items are not available online or do not belong to your account'
            ], 422);
        }

        $onlineStore->update(['menu_items' => $validated['menu_items']]);
        
        // Clear cache when online stores are modified
        cache()->forget("fnb_online_stores_{$clientIdentifier}");
        
        return response()->json([
            'status' => 'success',
            'message' => 'Menu items updated successfully',
            'data' => [
                'id' => $onlineStore->id,
                'name' => $onlineStore->name,
                'menu_items' => $onlineStore->menu_items
            ]
        ]);
    }

    /**
     * Get menu items for online store
     */
    public function getMenuItems(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        
        $onlineStore = FnbOnlineStore::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$onlineStore) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online store not found'
            ], 404);
        }

        $menuItems = $onlineStore->getMenuItems();

        return response()->json([
            'status' => 'success',
            'message' => 'Menu items retrieved successfully',
            'data' => [
                'fnb_menu_items' => $menuItems
            ]
        ]);
    }

    /**
     * Get public online store by domain (no authentication required)
     */
    public function getPublicStore(Request $request)
    {
        $domain = $request->query('domain');
        
        if (!$domain) {
            return response()->json([
                'status' => 'error',
                'message' => 'Domain is required'
            ], 400);
        }

        $onlineStore = FnbOnlineStore::where('domain', $domain)
            ->where('is_active', true)
            ->first();

        if (!$onlineStore) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online store not found or inactive'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Online store retrieved successfully',
            'data' => [
                'id' => $onlineStore->id,
                'name' => $onlineStore->name,
                'description' => $onlineStore->description,
                'domain' => $onlineStore->domain,
                'verification_required' => $onlineStore->verification_required,
                'payment_negotiation_enabled' => $onlineStore->payment_negotiation_enabled,
                'settings' => $onlineStore->settings ?? [],
            ]
        ]);
    }

    /**
     * Get public online store menu items (no authentication required)
     */
    public function getPublicStoreMenu(Request $request, $id)
    {
        $domain = $request->query('domain');
        
        if (!$domain) {
            return response()->json([
                'status' => 'error',
                'message' => 'Domain is required'
            ], 400);
        }

        $onlineStore = FnbOnlineStore::where('id', $id)
            ->where('domain', $domain)
            ->where('is_active', true)
            ->first();

        if (!$onlineStore) {
            return response()->json([
                'status' => 'error',
                'message' => 'Online store not found or inactive'
            ], 404);
        }

        $menuItems = $onlineStore->getMenuItems();

        return response()->json([
            'status' => 'success',
            'message' => 'Menu items retrieved successfully',
            'data' => $menuItems
        ]);
    }
}