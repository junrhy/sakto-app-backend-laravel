<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodDeliveryRestaurant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FoodDeliveryRestaurantController extends Controller
{
    /**
     * Display a listing of restaurants
     */
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        $query = FoodDeliveryRestaurant::where('client_identifier', $clientIdentifier)
            ->with(['menuItems' => function($q) {
                $q->where('is_available', true);
            }]);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'active');
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by rating
        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        // Filter by delivery fee range
        if ($request->has('max_delivery_fee')) {
            $query->where('delivery_fee', '<=', $request->max_delivery_fee);
        }

        // Filter by minimum order amount
        if ($request->has('max_minimum_order')) {
            $query->where('minimum_order_amount', '<=', $request->max_minimum_order);
        }

        // Filter by estimated prep time
        if ($request->has('max_prep_time')) {
            $query->where('estimated_prep_time', '<=', $request->max_prep_time);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'rating');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if ($sortBy === 'rating') {
            $query->orderBy('rating', $sortOrder)->orderBy('name', 'asc');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $restaurants = $query->get();

        return response()->json([
            'success' => true,
            'data' => $restaurants
        ]);
    }

    /**
     * Store a newly created restaurant
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'cover_image' => 'nullable|string',
            'address' => 'required|string',
            'coordinates' => 'nullable|string',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'operating_hours' => 'nullable|array',
            'delivery_zones' => 'nullable|array',
            'status' => ['nullable', Rule::in(['active', 'inactive', 'closed'])],
            'rating' => 'nullable|numeric|min:0|max:5',
            'delivery_fee' => 'nullable|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'estimated_prep_time' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['rating'] = $validated['rating'] ?? 0;
        $validated['delivery_fee'] = $validated['delivery_fee'] ?? 0;
        $validated['minimum_order_amount'] = $validated['minimum_order_amount'] ?? 0;
        $validated['estimated_prep_time'] = $validated['estimated_prep_time'] ?? 30;

        $restaurant = FoodDeliveryRestaurant::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Restaurant created successfully',
            'data' => $restaurant
        ], 201);
    }

    /**
     * Display the specified restaurant
     */
    public function show($id, Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $restaurant = FoodDeliveryRestaurant::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->with(['menuItems' => function($q) {
                $q->with('category')->orderBy('category_id')->orderBy('name');
            }])
            ->first();

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $restaurant
        ]);
    }

    /**
     * Update the specified restaurant
     */
    public function update(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $restaurant = FoodDeliveryRestaurant::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'cover_image' => 'nullable|string',
            'address' => 'sometimes|string',
            'coordinates' => 'nullable|string',
            'phone' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'operating_hours' => 'nullable|array',
            'delivery_zones' => 'nullable|array',
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'closed'])],
            'rating' => 'nullable|numeric|min:0|max:5',
            'delivery_fee' => 'nullable|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'estimated_prep_time' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $restaurant->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Restaurant updated successfully',
            'data' => $restaurant
        ]);
    }

    /**
     * Remove the specified restaurant
     */
    public function destroy($id, Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $restaurant = FoodDeliveryRestaurant::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found'
            ], 404);
        }

        // Check if restaurant has active orders
        $activeOrders = $restaurant->orders()
            ->whereIn('order_status', ['pending', 'accepted', 'preparing', 'ready', 'assigned', 'out_for_delivery'])
            ->count();

        if ($activeOrders > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete restaurant with active orders'
            ], 400);
        }

        $restaurant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Restaurant deleted successfully'
        ]);
    }
}
