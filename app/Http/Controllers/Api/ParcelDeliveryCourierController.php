<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParcelDeliveryCourier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ParcelDeliveryCourierController extends Controller
{
    /**
     * Display a listing of couriers
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

        $query = ParcelDeliveryCourier::where('client_identifier', $clientIdentifier);

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

        $couriers = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $couriers
        ]);
    }

    /**
     * Store a newly created courier
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'vehicle_type' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['available', 'busy', 'offline'])],
            'current_location' => 'nullable|string',
            'current_coordinates' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['status'] = $validated['status'] ?? 'available';

        $courier = ParcelDeliveryCourier::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Courier created successfully',
            'data' => $courier
        ], 201);
    }

    /**
     * Display the specified courier
     */
    public function show($id, Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $courier = ParcelDeliveryCourier::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->with('deliveries')
            ->first();

        if (!$courier) {
            return response()->json([
                'success' => false,
                'message' => 'Courier not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $courier
        ]);
    }

    /**
     * Update the specified courier
     */
    public function update(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $courier = ParcelDeliveryCourier::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$courier) {
            return response()->json([
                'success' => false,
                'message' => 'Courier not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'vehicle_type' => 'nullable|string|max:255',
            'status' => ['sometimes', Rule::in(['available', 'busy', 'offline'])],
            'current_location' => 'nullable|string',
            'current_coordinates' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $courier->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Courier updated successfully',
            'data' => $courier
        ]);
    }

    /**
     * Remove the specified courier
     */
    public function destroy($id, Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $courier = ParcelDeliveryCourier::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$courier) {
            return response()->json([
                'success' => false,
                'message' => 'Courier not found'
            ], 404);
        }

        // Check if courier has active deliveries
        $activeDeliveries = $courier->deliveries()
            ->whereIn('status', ['pending', 'picked_up', 'in_transit'])
            ->count();

        if ($activeDeliveries > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete courier with active deliveries'
            ], 400);
        }

        $courier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Courier deleted successfully'
        ]);
    }
}

