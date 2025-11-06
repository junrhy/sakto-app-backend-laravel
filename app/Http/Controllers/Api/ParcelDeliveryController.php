<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParcelDelivery;
use App\Models\ParcelDeliveryCourier;
use App\Services\ParcelDeliveryPricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ParcelDeliveryController extends Controller
{
    /**
     * Display a listing of deliveries for a client
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

        $query = ParcelDelivery::where('client_identifier', $clientIdentifier)
            ->with(['courier']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('delivery_type')) {
            $query->where('delivery_type', $request->delivery_type);
        }

        if ($request->has('courier_id')) {
            $query->where('courier_id', $request->courier_id);
        }

        if ($request->has('delivery_reference')) {
            $query->where('delivery_reference', $request->delivery_reference);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sender_name', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhere('delivery_reference', 'like', "%{$search}%")
                  ->orWhere('sender_address', 'like', "%{$search}%")
                  ->orWhere('recipient_address', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $deliveries = $query->get();

        return response()->json([
            'success' => true,
            'data' => $deliveries
        ]);
    }

    /**
     * Store a newly created delivery
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'delivery_type' => ['required', Rule::in(['express', 'standard', 'economy'])],
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:255',
            'sender_email' => 'nullable|email|max:255',
            'sender_address' => 'required|string',
            'sender_coordinates' => 'nullable|string',
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:255',
            'recipient_email' => 'nullable|email|max:255',
            'recipient_address' => 'required|string',
            'recipient_coordinates' => 'nullable|string',
            'package_description' => 'required|string',
            'package_weight' => 'required|numeric|min:0.01',
            'package_length' => 'nullable|numeric|min:0',
            'package_width' => 'nullable|numeric|min:0',
            'package_height' => 'nullable|numeric|min:0',
            'package_value' => 'nullable|numeric|min:0',
            'distance_km' => 'nullable|numeric|min:0',
            'pickup_date' => 'required|date|after_or_equal:today',
            'pickup_time' => 'required|date_format:H:i',
            'special_instructions' => 'nullable|string',
            'is_urgent' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Generate delivery reference
        $validated['delivery_reference'] = ParcelDelivery::generateDeliveryReference();

        // Calculate pricing using the pricing service
        $pricingService = new ParcelDeliveryPricingService($validated['client_identifier']);
        $pricing = $pricingService->calculatePricing($validated);

        // Merge pricing data with validated data
        $validated = array_merge($validated, [
            'base_rate' => $pricing['base_rate'],
            'distance_rate' => $pricing['distance_rate'],
            'weight_rate' => $pricing['weight_rate'],
            'size_rate' => $pricing['size_rate'],
            'delivery_type_multiplier' => $pricing['delivery_type_multiplier'],
            'estimated_cost' => $pricing['estimated_cost'],
            'pricing_breakdown' => $pricing['pricing_breakdown'],
        ]);

        $delivery = ParcelDelivery::create($validated);

        // Create initial tracking entry
        $delivery->updateStatus('pending', null, 'Delivery request created', 'system');

        return response()->json([
            'success' => true,
            'message' => 'Delivery created successfully',
            'data' => $delivery->load('courier')
        ], 201);
    }

    /**
     * Display the specified delivery
     */
    public function show($id, Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $delivery = ParcelDelivery::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->with(['courier', 'trackings' => function($query) {
                $query->orderBy('timestamp', 'desc');
            }])
            ->first();

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $delivery
        ]);
    }

    /**
     * Update the specified delivery
     */
    public function update(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $delivery = ParcelDelivery::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'delivery_type' => ['sometimes', Rule::in(['express', 'standard', 'economy'])],
            'sender_name' => 'sometimes|string|max:255',
            'sender_phone' => 'sometimes|string|max:255',
            'sender_email' => 'nullable|email|max:255',
            'sender_address' => 'sometimes|string',
            'sender_coordinates' => 'nullable|string',
            'recipient_name' => 'sometimes|string|max:255',
            'recipient_phone' => 'sometimes|string|max:255',
            'recipient_email' => 'nullable|email|max:255',
            'recipient_address' => 'sometimes|string',
            'recipient_coordinates' => 'nullable|string',
            'package_description' => 'sometimes|string',
            'package_weight' => 'sometimes|numeric|min:0.01',
            'package_length' => 'nullable|numeric|min:0',
            'package_width' => 'nullable|numeric|min:0',
            'package_height' => 'nullable|numeric|min:0',
            'package_value' => 'nullable|numeric|min:0',
            'distance_km' => 'nullable|numeric|min:0',
            'pickup_date' => 'sometimes|date',
            'pickup_time' => 'sometimes|date_format:H:i',
            'special_instructions' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Recalculate pricing if relevant fields changed
        if (isset($validated['delivery_type']) || isset($validated['package_weight']) || 
            isset($validated['distance_km']) || isset($validated['package_length'])) {
            $pricingService = new ParcelDeliveryPricingService($clientIdentifier);
            $deliveryData = array_merge($delivery->toArray(), $validated);
            $pricing = $pricingService->calculatePricing($deliveryData);
            
            $validated['base_rate'] = $pricing['base_rate'];
            $validated['distance_rate'] = $pricing['distance_rate'];
            $validated['weight_rate'] = $pricing['weight_rate'];
            $validated['size_rate'] = $pricing['size_rate'];
            $validated['delivery_type_multiplier'] = $pricing['delivery_type_multiplier'];
            $validated['estimated_cost'] = $pricing['estimated_cost'];
            $validated['pricing_breakdown'] = $pricing['pricing_breakdown'];
        }

        $delivery->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Delivery updated successfully',
            'data' => $delivery->load('courier')
        ]);
    }

    /**
     * Remove the specified delivery
     */
    public function destroy($id, Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $delivery = ParcelDelivery::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery not found'
            ], 404);
        }

        // Only allow deletion if pending
        if ($delivery->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending deliveries can be deleted'
            ], 400);
        }

        $delivery->delete();

        return response()->json([
            'success' => true,
            'message' => 'Delivery deleted successfully'
        ]);
    }

    /**
     * Calculate pricing for a delivery
     */
    public function calculatePricing(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'delivery_type' => ['required', Rule::in(['express', 'standard', 'economy'])],
            'package_weight' => 'required|numeric|min:0.01',
            'distance_km' => 'required|numeric|min:0',
            'package_length' => 'nullable|numeric|min:0',
            'package_width' => 'nullable|numeric|min:0',
            'package_height' => 'nullable|numeric|min:0',
            'package_value' => 'nullable|numeric|min:0',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required|date_format:H:i',
            'is_urgent' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $pricingService = new ParcelDeliveryPricingService($request->input('client_identifier'));
        $pricing = $pricingService->calculatePricing($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $pricing
        ]);
    }

    /**
     * Assign courier to delivery
     */
    public function assignCourier(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $validator = Validator::make($request->all(), [
            'courier_id' => 'required|exists:parcel_delivery_couriers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $delivery = ParcelDelivery::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery not found'
            ], 404);
        }

        $courier = ParcelDeliveryCourier::where('id', $request->courier_id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$courier) {
            return response()->json([
                'success' => false,
                'message' => 'Courier not found'
            ], 404);
        }

        if (!$courier->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Courier is not available'
            ], 400);
        }

        $delivery->assignCourier($request->courier_id);

        return response()->json([
            'success' => true,
            'message' => 'Courier assigned successfully',
            'data' => $delivery->load('courier')
        ]);
    }

    /**
     * Update delivery status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in([
                'pending',
                'confirmed',
                'scheduled',
                'out_for_pickup',
                'picked_up',
                'at_warehouse',
                'in_transit',
                'out_for_delivery',
                'delivery_attempted',
                'delivered',
                'returned',
                'returned_to_sender',
                'on_hold',
                'failed',
                'cancelled'
            ])],
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $delivery = ParcelDelivery::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery not found'
            ], 404);
        }

        $delivery->updateStatus(
            $request->status,
            $request->location,
            $request->notes,
            $request->input('updated_by', 'system')
        );

        // If cancelled or delivered, mark courier as available
        if (in_array($request->status, ['cancelled', 'delivered']) && $delivery->courier_id) {
            $courier = ParcelDeliveryCourier::find($delivery->courier_id);
            if ($courier) {
                $courier->markAsAvailable();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => $delivery->load(['courier', 'trackings'])
        ]);
    }

    /**
     * Get delivery by reference (public tracking)
     */
    public function getByReference($reference): JsonResponse
    {
        $delivery = ParcelDelivery::where('delivery_reference', $reference)
            ->with(['trackings' => function($query) {
                $query->orderBy('timestamp', 'asc');
            }])
            ->first();

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $delivery
        ]);
    }
}

