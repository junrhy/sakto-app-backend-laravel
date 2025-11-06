<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodDeliveryMenuCategory;
use App\Models\FoodDeliveryMenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FoodDeliveryMenuController extends Controller
{
    // ==================== CATEGORIES ====================

    /**
     * Display a listing of menu categories
     */
    public function categories(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        $categories = FoodDeliveryMenuCategory::where('client_identifier', $clientIdentifier)
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Store a newly created category
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['display_order'] = $validated['display_order'] ?? 0;

        $category = FoodDeliveryMenuCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Update the specified category
     */
    public function updateCategory(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $category = FoodDeliveryMenuCategory::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroyCategory($id, Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $category = FoodDeliveryMenuCategory::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    // ==================== MENU ITEMS ====================

    /**
     * Display a listing of menu items
     */
    public function items(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        $query = FoodDeliveryMenuItem::where('client_identifier', $clientIdentifier)
            ->with(['restaurant', 'category']);

        // Filter by restaurant
        if ($request->has('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by availability
        if ($request->has('is_available')) {
            $query->where('is_available', $request->is_available);
        }

        // Filter by featured
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->is_featured);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('category_id')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * Store a newly created menu item
     */
    public function storeItem(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'restaurant_id' => 'required|exists:food_delivery_restaurants,id',
            'category_id' => 'nullable|exists:food_delivery_menu_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'is_available' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'preparation_time' => 'nullable|integer|min:1',
            'dietary_info' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['is_available'] = $validated['is_available'] ?? true;
        $validated['is_featured'] = $validated['is_featured'] ?? false;
        $validated['preparation_time'] = $validated['preparation_time'] ?? 15;

        $item = FoodDeliveryMenuItem::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Menu item created successfully',
            'data' => $item->load(['restaurant', 'category'])
        ], 201);
    }

    /**
     * Display the specified menu item
     */
    public function showItem($id, Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $item = FoodDeliveryMenuItem::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->with(['restaurant', 'category'])
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Menu item not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    /**
     * Update the specified menu item
     */
    public function updateItem(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $item = FoodDeliveryMenuItem::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Menu item not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'sometimes|exists:food_delivery_restaurants,id',
            'category_id' => 'nullable|exists:food_delivery_menu_categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'is_available' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'preparation_time' => 'nullable|integer|min:1',
            'dietary_info' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $item->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Menu item updated successfully',
            'data' => $item->load(['restaurant', 'category'])
        ]);
    }

    /**
     * Remove the specified menu item
     */
    public function destroyItem($id, Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $item = FoodDeliveryMenuItem::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Menu item not found'
            ], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu item deleted successfully'
        ]);
    }
}
