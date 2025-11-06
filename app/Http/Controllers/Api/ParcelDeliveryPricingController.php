<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParcelDeliveryPricingConfig;
use App\Services\ParcelDeliveryPricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ParcelDeliveryPricingController extends Controller
{
    /**
     * Get pricing configuration for a client
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

        $config = ParcelDeliveryPricingConfig::getDefaultConfig($clientIdentifier);
        
        if (!$config) {
            $config = ParcelDeliveryPricingConfig::createDefaultConfig($clientIdentifier);
        }

        return response()->json([
            'success' => true,
            'data' => $config
        ]);
    }

    /**
     * Update pricing configuration
     */
    public function update(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'config_name' => 'sometimes|string|max:255',
            'base_rates' => 'sometimes|array',
            'distance_rate_per_km' => 'sometimes|numeric|min:0',
            'weight_rates' => 'sometimes|array',
            'size_rate_per_cubic_cm' => 'sometimes|numeric|min:0',
            'delivery_type_multipliers' => 'sometimes|array',
            'surcharges' => 'sometimes|array',
            'peak_hours' => 'sometimes|array',
            'holidays' => 'sometimes|array',
            'insurance_rate' => 'sometimes|numeric|min:0|max:1',
            'minimum_charge' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $config = ParcelDeliveryPricingConfig::getDefaultConfig($clientIdentifier);
        
        if (!$config) {
            $config = ParcelDeliveryPricingConfig::createDefaultConfig($clientIdentifier);
        }

        $config->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Pricing configuration updated successfully',
            'data' => $config
        ]);
    }

    /**
     * Calculate pricing
     */
    public function calculate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'delivery_type' => 'required|in:express,standard,economy',
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
}

