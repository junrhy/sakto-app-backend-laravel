<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CargoUnloading;
use App\Models\TransportationCargoMonitoring;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CargoUnloadingController extends Controller
{
    /**
     * Display a listing of unloading records for a cargo item
     */
    public function index(Request $request, $cargoItemId): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        // Verify cargo item belongs to client
        $cargoItem = TransportationCargoMonitoring::findOrFail($cargoItemId);
        if ($cargoItem->client_identifier !== $clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $unloadings = CargoUnloading::byClient($clientIdentifier)
            ->byCargoItem($cargoItemId)
            ->orderBy('unloaded_at', 'desc')
            ->get();

        return response()->json($unloadings);
    }

    /**
     * Store a newly created unloading record
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'cargo_item_id' => 'required|exists:transportation_cargo_monitorings,id',
            'quantity_unloaded' => 'required|integer|min:1',
            'unload_location' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'unloaded_at' => 'required|date',
            'unloaded_by' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // Verify cargo item belongs to client
        $cargoItem = TransportationCargoMonitoring::findOrFail($validated['cargo_item_id']);
        if ($cargoItem->client_identifier !== $validated['client_identifier']) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Check if unloading quantity exceeds remaining quantity
        $remainingQuantity = $cargoItem->remaining_quantity;
        if ($validated['quantity_unloaded'] > $remainingQuantity) {
            return response()->json([
                'success' => false,
                'message' => "Cannot unload {$validated['quantity_unloaded']} items. Only {$remainingQuantity} items remaining."
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create unloading record
            $unloading = CargoUnloading::create($validated);

            // Update cargo status if fully unloaded
            if ($cargoItem->is_fully_unloaded) {
                $cargoItem->update(['status' => 'Delivered']);
            } elseif ($cargoItem->is_partially_unloaded) {
                $cargoItem->update(['status' => 'In Transit']);
            }

            DB::commit();

            return response()->json($unloading, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create unloading record'
            ], 500);
        }
    }

    /**
     * Display the specified unloading record
     */
    public function show(Request $request, $cargoItemId, $unloadingId): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }
        
        $unloading = CargoUnloading::findOrFail($unloadingId);
        
        if ($unloading->client_identifier !== $clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $unloading->load('cargoItem');
        return response()->json($unloading);
    }

    /**
     * Update the specified unloading record
     */
    public function update(Request $request, $cargoItemId, $unloadingId): JsonResponse
    {
        $unloading = CargoUnloading::findOrFail($unloadingId);
        
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'quantity_unloaded' => 'required|integer|min:1',
            'unload_location' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'unloaded_at' => 'required|date',
            'unloaded_by' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        
        // Check if client_identifier matches the resource
        if ($unloading->client_identifier !== $validated['client_identifier']) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Get cargo item to check remaining quantity
        $cargoItem = $unloading->cargoItem;
        $currentUnloadedQuantity = $unloading->quantity_unloaded;
        $newUnloadedQuantity = $validated['quantity_unloaded'];
        $quantityDifference = $newUnloadedQuantity - $currentUnloadedQuantity;
        
        // Check if new quantity exceeds remaining quantity
        if ($quantityDifference > $cargoItem->remaining_quantity) {
            return response()->json([
                'success' => false,
                'message' => "Cannot update unloading quantity. Would exceed remaining quantity."
            ], 422);
        }

        DB::beginTransaction();
        try {
            $unloading->update($validated);

            // Update cargo status if fully unloaded
            if ($cargoItem->is_fully_unloaded) {
                $cargoItem->update(['status' => 'Delivered']);
            } elseif ($cargoItem->is_partially_unloaded) {
                $cargoItem->update(['status' => 'In Transit']);
            }

            DB::commit();

            return response()->json($unloading);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update unloading record'
            ], 500);
        }
    }

    /**
     * Remove the specified unloading record
     */
    public function destroy(Request $request, $cargoItemId, $unloadingId): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }
        
        $unloading = CargoUnloading::findOrFail($unloadingId);
        
        if ($unloading->client_identifier !== $clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $cargoItem = $unloading->cargoItem;
            $unloading->delete();

            // Update cargo status after deletion
            if ($cargoItem->is_fully_unloaded) {
                $cargoItem->update(['status' => 'Delivered']);
            } elseif ($cargoItem->is_partially_unloaded) {
                $cargoItem->update(['status' => 'In Transit']);
            } else {
                $cargoItem->update(['status' => 'Loaded']);
            }

            DB::commit();

            return response()->json(['message' => 'Unloading record deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete unloading record'
            ], 500);
        }
    }

    /**
     * Get unloading summary for a cargo item
     */
    public function summary(Request $request, $cargoItemId): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        // Verify cargo item belongs to client
        $cargoItem = TransportationCargoMonitoring::findOrFail($cargoItemId);
        if ($cargoItem->client_identifier !== $clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $summary = [
            'total_quantity' => $cargoItem->quantity,
            'total_unloaded' => $cargoItem->total_unloaded_quantity,
            'remaining_quantity' => $cargoItem->remaining_quantity,
            'is_fully_unloaded' => $cargoItem->is_fully_unloaded,
            'is_partially_unloaded' => $cargoItem->is_partially_unloaded,
            'unloading_count' => $cargoItem->unloadings()->count(),
            'unloadings' => $cargoItem->unloadings()->orderBy('unloaded_at', 'desc')->get()
        ];

        return response()->json($summary);
    }
}
