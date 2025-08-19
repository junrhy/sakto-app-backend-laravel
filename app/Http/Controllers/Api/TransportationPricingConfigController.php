<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransportationPricingConfig;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TransportationPricingConfigController extends Controller
{
    /**
     * Get all pricing configurations for a client
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $configs = TransportationPricingConfig::active()
            ->forClient($request->client_identifier)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $configs,
            'message' => 'Pricing configurations retrieved successfully'
        ]);
    }

    /**
     * Get a specific pricing configuration
     */
    public function show($id): JsonResponse
    {
        $config = TransportationPricingConfig::find($id);

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Pricing configuration not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $config,
            'message' => 'Pricing configuration retrieved successfully'
        ]);
    }

    /**
     * Store a new pricing configuration
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'config_name' => 'required|string|max:255',
            'config_type' => 'required|string|max:255',
            'base_rates' => 'nullable|array',
            'distance_rates' => 'nullable|array',
            'weight_rates' => 'nullable|array',
            'special_handling_rates' => 'nullable|array',
            'surcharges' => 'nullable|array',
            'peak_hours' => 'nullable|array',
            'holidays' => 'nullable|array',
            'additional_costs' => 'nullable|array',
            'overtime_hours' => 'nullable|array',
            'currency' => 'nullable|string|max:10',
            'currency_symbol' => 'nullable|string|max:10',
            'decimal_places' => 'nullable|integer|min:0|max:4',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $config = TransportationPricingConfig::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $config,
            'message' => 'Pricing configuration created successfully'
        ], 201);
    }

    /**
     * Update a pricing configuration
     */
    public function update(Request $request, $id): JsonResponse
    {
        $config = TransportationPricingConfig::find($id);

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Pricing configuration not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'config_name' => 'sometimes|required|string|max:255',
            'config_type' => 'sometimes|required|string|max:255',
            'base_rates' => 'nullable|array',
            'distance_rates' => 'nullable|array',
            'weight_rates' => 'nullable|array',
            'special_handling_rates' => 'nullable|array',
            'surcharges' => 'nullable|array',
            'peak_hours' => 'nullable|array',
            'holidays' => 'nullable|array',
            'additional_costs' => 'nullable|array',
            'overtime_hours' => 'nullable|array',
            'currency' => 'nullable|string|max:10',
            'currency_symbol' => 'nullable|string|max:10',
            'decimal_places' => 'nullable|integer|min:0|max:4',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $config->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $config,
            'message' => 'Pricing configuration updated successfully'
        ]);
    }

    /**
     * Delete a pricing configuration
     */
    public function destroy($id): JsonResponse
    {
        $config = TransportationPricingConfig::find($id);

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Pricing configuration not found'
            ], 404);
        }

        // Don't allow deletion of default configurations
        if ($config->config_type === 'default') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete default pricing configuration'
            ], 400);
        }

        $config->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pricing configuration deleted successfully'
        ]);
    }

    /**
     * Get default pricing configuration for a client
     */
    public function getDefault(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $config = TransportationPricingConfig::getDefaultConfig($request->client_identifier);

        if (!$config) {
            // Create default config if none exists
            $config = TransportationPricingConfig::createDefaultConfig($request->client_identifier);
        }

        return response()->json([
            'success' => true,
            'data' => $config,
            'message' => 'Default pricing configuration retrieved successfully'
        ]);
    }

    /**
     * Calculate pricing preview using a specific configuration
     */
    public function calculatePreview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'config_id' => 'nullable|integer|exists:transportation_pricing_configs,id',
            'truck_id' => 'required|integer|exists:transportation_fleets,id',
            'pickup_date' => 'required|date|after_or_equal:today',
            'pickup_time' => 'required|date_format:H:i',
            'delivery_date' => 'required|date|after_or_equal:pickup_date',
            'delivery_time' => 'required|date_format:H:i',
            'cargo_weight' => 'required|numeric|min:0.01',
            'cargo_unit' => 'required|string',
            'distance_km' => 'nullable|numeric|min:0',
            'route_type' => 'nullable|string',
            'requires_refrigeration' => 'nullable|boolean',
            'requires_special_equipment' => 'nullable|boolean',
            'requires_escort' => 'nullable|boolean',
            'is_urgent_delivery' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the pricing configuration
        if ($request->config_id) {
            $config = TransportationPricingConfig::find($request->config_id);
        } else {
            $config = TransportationPricingConfig::getDefaultConfig($request->client_identifier);
        }

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Pricing configuration not found'
            ], 404);
        }

        // Create pricing service with the specific config
        $pricingService = new \App\Services\TransportationPricingService();
        $pricingService->setConfig($config);

        $pricing = $pricingService->calculatePricing($request->all());

        return response()->json([
            'success' => true,
            'data' => $pricing,
            'config_used' => $config->config_name,
            'message' => 'Pricing preview calculated successfully'
        ]);
    }
}
