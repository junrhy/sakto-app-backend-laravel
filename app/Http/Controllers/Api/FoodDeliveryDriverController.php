<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodDeliveryDriver;
use App\Models\FoodDeliveryOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FoodDeliveryDriverController extends Controller
{
    /**
     * Display a listing of drivers
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

        $query = FoodDeliveryDriver::where('client_identifier', $clientIdentifier);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $drivers = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $drivers
        ]);
    }

    /**
     * Store a newly created driver
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'vehicle_type' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['available', 'busy', 'offline'])],
            'current_location' => 'nullable|string',
            'current_coordinates' => 'nullable|string',
            'rating' => 'nullable|numeric|min:0|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['status'] = $validated['status'] ?? 'available';
        $validated['rating'] = $validated['rating'] ?? 0;
        $validated['total_deliveries'] = 0;

        $driver = FoodDeliveryDriver::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Driver created successfully',
            'data' => $driver
        ], 201);
    }

    /**
     * Display the specified driver
     */
    public function show($id, Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $driver = FoodDeliveryDriver::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->with(['orders' => function($q) {
                $q->orderBy('created_at', 'desc')->limit(10);
            }])
            ->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $driver
        ]);
    }

    /**
     * Update the specified driver
     */
    public function update(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $driver = FoodDeliveryDriver::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'vehicle_type' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:255',
            'status' => ['sometimes', Rule::in(['available', 'busy', 'offline'])],
            'current_location' => 'nullable|string',
            'current_coordinates' => 'nullable|string',
            'rating' => 'nullable|numeric|min:0|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $driver->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Driver updated successfully',
            'data' => $driver
        ]);
    }

    /**
     * Remove the specified driver
     */
    public function destroy($id, Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $driver = FoodDeliveryDriver::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not found'
            ], 404);
        }

        // Check if driver has active orders
        $activeOrders = $driver->orders()
            ->whereIn('order_status', ['pending', 'accepted', 'preparing', 'ready', 'assigned', 'out_for_delivery'])
            ->count();

        if ($activeOrders > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete driver with active orders'
            ], 400);
        }

        $driver->delete();

        return response()->json([
            'success' => true,
            'message' => 'Driver deleted successfully'
        ]);
    }

    /**
     * Find nearest available driver for order
     */
    public function findNearest(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        if (!$clientIdentifier || !$latitude || !$longitude) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier, latitude, and longitude are required'
            ], 400);
        }

        $availableDrivers = FoodDeliveryDriver::where('client_identifier', $clientIdentifier)
            ->where('status', 'available')
            ->whereNotNull('current_coordinates')
            ->get();

        if ($availableDrivers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No available drivers found'
            ], 404);
        }

        // Calculate distance for each driver and find nearest
        $nearestDriver = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($availableDrivers as $driver) {
            $distance = $driver->distanceTo($latitude, $longitude);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestDriver = $driver;
            }
        }

        if (!$nearestDriver) {
            return response()->json([
                'success' => false,
                'message' => 'No available drivers found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'driver' => $nearestDriver,
                'distance_km' => round($minDistance, 2)
            ]
        ]);
    }
}
