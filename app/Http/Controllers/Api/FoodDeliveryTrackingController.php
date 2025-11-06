<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodDeliveryOrderTracking;
use App\Models\FoodDeliveryOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FoodDeliveryTrackingController extends Controller
{
    /**
     * Display tracking history for an order
     */
    public function index(Request $request, $orderId): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $order = FoodDeliveryOrder::where('id', $orderId)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $trackings = FoodDeliveryOrderTracking::where('order_id', $orderId)
            ->orderBy('timestamp', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trackings
        ]);
    }

    /**
     * Store a new tracking entry
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:food_delivery_orders,id',
            'status' => ['required', Rule::in([
                'pending',
                'accepted',
                'preparing',
                'ready',
                'assigned',
                'out_for_delivery',
                'delivered',
                'cancelled'
            ])],
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
            'updated_by' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['timestamp'] = now();
        $validated['updated_by'] = $validated['updated_by'] ?? 'system';

        $tracking = FoodDeliveryOrderTracking::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tracking entry created successfully',
            'data' => $tracking
        ], 201);
    }
}
