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
        $query = TransportationCargoMonitoring::query();

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

        $cargoItem = TransportationCargoMonitoring::create($validator->validated());

        return response()->json($cargoItem, 201);
    }

    /**
     * Display the specified cargo item
     */
    public function show(TransportationCargoMonitoring $transportationCargoMonitoring): JsonResponse
    {
        $cargoItem = $transportationCargoMonitoring->load(['shipment.truck']);
        return response()->json($cargoItem);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransportationCargoMonitoring $transportationCargoMonitoring)
    {
        //
    }

    /**
     * Update the specified cargo item
     */
    public function update(Request $request, TransportationCargoMonitoring $transportationCargoMonitoring): JsonResponse
    {
        $validator = Validator::make($request->all(), [
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

        $transportationCargoMonitoring->update($validator->validated());

        return response()->json($transportationCargoMonitoring);
    }

    /**
     * Remove the specified cargo item
     */
    public function destroy(TransportationCargoMonitoring $transportationCargoMonitoring): JsonResponse
    {
        $transportationCargoMonitoring->delete();
        return response()->json(['message' => 'Cargo item deleted successfully']);
    }

    /**
     * Update cargo status
     */
    public function updateStatus(Request $request, TransportationCargoMonitoring $transportationCargoMonitoring): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['Loaded', 'In Transit', 'Delivered', 'Damaged'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $transportationCargoMonitoring->update(['status' => $request->status]);

        return response()->json(['message' => 'Status updated successfully']);
    }

    /**
     * Get cargo items by shipment
     */
    public function byShipment(TransportationShipmentTracking $shipment): JsonResponse
    {
        $cargoItems = $shipment->cargoItems()->get();
        return response()->json($cargoItems);
    }

    /**
     * Get dashboard stats
     */
    public function dashboardStats(): JsonResponse
    {
        $stats = [
            'total_cargo_items' => TransportationCargoMonitoring::count(),
            'loaded_cargo' => TransportationCargoMonitoring::loaded()->count(),
            'in_transit_cargo' => TransportationCargoMonitoring::inTransit()->count(),
            'delivered_cargo' => TransportationCargoMonitoring::delivered()->count(),
            'damaged_cargo' => TransportationCargoMonitoring::damaged()->count(),
        ];

        return response()->json($stats);
    }
}
