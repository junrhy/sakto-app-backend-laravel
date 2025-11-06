<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParcelDeliveryExternalIntegration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ParcelDeliveryExternalController extends Controller
{
    /**
     * Display a listing of external integrations
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

        $integrations = ParcelDeliveryExternalIntegration::where('client_identifier', $clientIdentifier)
            ->get()
            ->map(function ($integration) {
                // Hide sensitive data
                $integration->api_secret = $integration->api_secret ? '***' : null;
                return $integration;
            });

        return response()->json([
            'success' => true,
            'data' => $integrations
        ]);
    }

    /**
     * Store a newly created external integration
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'service_name' => 'required|string|max:255',
            'api_key' => 'nullable|string|max:255',
            'api_secret' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'settings' => 'nullable|array',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['is_active'] = $validated['is_active'] ?? false;

        $integration = ParcelDeliveryExternalIntegration::create($validated);

        // Hide sensitive data in response
        $integration->api_secret = $integration->api_secret ? '***' : null;

        return response()->json([
            'success' => true,
            'message' => 'External integration created successfully',
            'data' => $integration
        ], 201);
    }

    /**
     * Update the specified external integration
     */
    public function update(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $integration = ParcelDeliveryExternalIntegration::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$integration) {
            return response()->json([
                'success' => false,
                'message' => 'Integration not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'service_name' => 'sometimes|string|max:255',
            'api_key' => 'nullable|string|max:255',
            'api_secret' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'settings' => 'nullable|array',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        
        // Only update api_secret if provided
        if (!isset($validated['api_secret'])) {
            unset($validated['api_secret']);
        }

        $integration->update($validated);

        // Hide sensitive data in response
        $integration->api_secret = $integration->api_secret ? '***' : null;

        return response()->json([
            'success' => true,
            'message' => 'External integration updated successfully',
            'data' => $integration
        ]);
    }

    /**
     * Remove the specified external integration
     */
    public function destroy($id, Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $integration = ParcelDeliveryExternalIntegration::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$integration) {
            return response()->json([
                'success' => false,
                'message' => 'Integration not found'
            ], 404);
        }

        $integration->delete();

        return response()->json([
            'success' => true,
            'message' => 'External integration deleted successfully'
        ]);
    }
}

