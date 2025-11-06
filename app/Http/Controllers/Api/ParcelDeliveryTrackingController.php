<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParcelDeliveryTracking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ParcelDeliveryTrackingController extends Controller
{
    /**
     * Get tracking history for a delivery
     */
    public function index($deliveryId, Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $delivery = \App\Models\ParcelDelivery::where('id', $deliveryId)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery not found'
            ], 404);
        }

        $trackings = ParcelDeliveryTracking::where('parcel_delivery_id', $deliveryId)
            ->orderBy('timestamp', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trackings
        ]);
    }
}

