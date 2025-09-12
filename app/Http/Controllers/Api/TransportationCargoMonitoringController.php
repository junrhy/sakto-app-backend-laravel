<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransportationCargoMonitoring;
use App\Models\TransportationShipmentTracking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransportationCargoMonitoringController extends Controller
{
    /**
     * Display a listing of cargo items
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

        $query = TransportationCargoMonitoring::where('client_identifier', $clientIdentifier);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('name')) {
            $query->byName($request->name);
        }

        if ($request->has('description')) {
            $query->byDescription($request->description);
        }

        if ($request->has('special_handling')) {
            $query->bySpecialHandling($request->special_handling);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('special_handling', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $cargoItems = $query->with(['shipment.truck'])->get();
        
        // Load unloadings separately to avoid serialization issues
        $cargoItems->load('unloadings');

        return response()->json($cargoItems);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created cargo item
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'shipment_id' => 'required|exists:transportation_shipment_trackings,id',
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit' => ['required', Rule::in(['kg', 'pieces', 'pallets', 'boxes'])],
            'description' => 'nullable|string',
            'special_handling' => 'nullable|string|max:255',
            'status' => ['required', Rule::in(['Loaded', 'In Transit', 'Delivered', 'Damaged'])],
            'temperature' => 'nullable|numeric|min:-50|max:100',
            'humidity' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify shipment belongs to the same client
        $shipment = TransportationShipmentTracking::find($request->shipment_id);
        if (!$shipment || $shipment->client_identifier !== $request->client_identifier) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid shipment or unauthorized access'
            ], 403);
        }

        $cargoItem = TransportationCargoMonitoring::create($validator->validated());

        return response()->json($cargoItem, 201);
    }

    /**
     * Display the specified cargo item
     */
    public function show(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }
        
        $transportationCargoMonitoring = TransportationCargoMonitoring::findOrFail($id);
        
        if ($transportationCargoMonitoring->client_identifier !== $clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $cargoItem = $transportationCargoMonitoring->load(['shipment.truck', 'unloadings']);
        return response()->json($cargoItem);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified cargo item
     */
    public function update(Request $request, $id): JsonResponse
    {
        $transportationCargoMonitoring = TransportationCargoMonitoring::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'shipment_id' => 'required|exists:transportation_shipment_trackings,id',
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit' => ['required', Rule::in(['kg', 'pieces', 'pallets', 'boxes'])],
            'description' => 'nullable|string',
            'special_handling' => 'nullable|string|max:255',
            'status' => ['required', Rule::in(['Loaded', 'In Transit', 'Delivered', 'Damaged'])],
            'temperature' => 'nullable|numeric|min:-50|max:100',
            'humidity' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        
        // Check if client_identifier matches the resource
        if ($transportationCargoMonitoring->client_identifier !== $validated['client_identifier']) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Verify shipment belongs to the same client
        $shipment = TransportationShipmentTracking::find($request->shipment_id);
        if (!$shipment || $shipment->client_identifier !== $validated['client_identifier']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid shipment or unauthorized access'
            ], 403);
        }

        $transportationCargoMonitoring->update($validated);

        return response()->json($transportationCargoMonitoring);
    }

    /**
     * Remove the specified cargo item
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }
        
        $transportationCargoMonitoring = TransportationCargoMonitoring::findOrFail($id);
        
        if ($transportationCargoMonitoring->client_identifier !== $clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $transportationCargoMonitoring->delete();
        return response()->json(['message' => 'Cargo item deleted successfully']);
    }

    /**
     * Update cargo status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $transportationCargoMonitoring = TransportationCargoMonitoring::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'status' => ['required', Rule::in(['Loaded', 'In Transit', 'Delivered', 'Damaged'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        
        // Check if client_identifier matches the resource
        if ($transportationCargoMonitoring->client_identifier !== $validated['client_identifier']) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $transportationCargoMonitoring->update(['status' => $validated['status']]);

        return response()->json(['message' => 'Status updated successfully']);
    }

    /**
     * Get cargo items by shipment
     */
    public function byShipment(Request $request, $shipmentId): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }
        
        $shipment = TransportationShipmentTracking::findOrFail($shipmentId);
        
        if ($shipment->client_identifier !== $clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $cargoItems = $shipment->cargoItems()->get();
        return response()->json($cargoItems);
    }

    /**
     * Get dashboard stats
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        // Current stats
        $currentStats = [
            'total_cargo_items' => TransportationCargoMonitoring::where('client_identifier', $clientIdentifier)->count(),
            'loaded_cargo' => TransportationCargoMonitoring::where('client_identifier', $clientIdentifier)->loaded()->count(),
            'in_transit_cargo' => TransportationCargoMonitoring::where('client_identifier', $clientIdentifier)->inTransit()->count(),
            'delivered_cargo' => TransportationCargoMonitoring::where('client_identifier', $clientIdentifier)->delivered()->count(),
            'damaged_cargo' => TransportationCargoMonitoring::where('client_identifier', $clientIdentifier)->damaged()->count(),
        ];

        // Previous month stats (30 days ago)
        $previousMonth = now()->subDays(30);
        $previousStats = [
            'total_cargo_items' => TransportationCargoMonitoring::where('client_identifier', $clientIdentifier)
                ->where('created_at', '<=', $previousMonth)->count(),
            'loaded_cargo' => TransportationCargoMonitoring::where('client_identifier', $clientIdentifier)
                ->loaded()->where('created_at', '<=', $previousMonth)->count(),
            'in_transit_cargo' => TransportationCargoMonitoring::where('client_identifier', $clientIdentifier)
                ->inTransit()->where('created_at', '<=', $previousMonth)->count(),
            'delivered_cargo' => TransportationCargoMonitoring::where('client_identifier', $clientIdentifier)
                ->delivered()->where('created_at', '<=', $previousMonth)->count(),
            'damaged_cargo' => TransportationCargoMonitoring::where('client_identifier', $clientIdentifier)
                ->damaged()->where('created_at', '<=', $previousMonth)->count(),
        ];

        // Calculate trends
        $trends = [];
        foreach ($currentStats as $key => $currentValue) {
            $previousValue = $previousStats[$key];
            if ($previousValue > 0) {
                $trend = (($currentValue - $previousValue) / $previousValue) * 100;
                $trends[$key . '_trend'] = round($trend, 1);
            } else {
                $trends[$key . '_trend'] = $currentValue > 0 ? 100 : 0;
            }
        }

        $stats = array_merge($currentStats, $trends);

        return response()->json($stats);
    }
}
