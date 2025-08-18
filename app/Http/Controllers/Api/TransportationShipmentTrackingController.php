<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransportationShipmentTracking;
use App\Models\TransportationTrackingUpdate;
use App\Models\TransportationFleet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransportationShipmentTrackingController extends Controller
{
    /**
     * Display a listing of shipments
     */
    public function index(Request $request): JsonResponse
    {
        $query = TransportationShipmentTracking::query();

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('driver')) {
            $query->byDriver($request->driver);
        }

        if ($request->has('destination')) {
            $query->byDestination($request->destination);
        }

        if ($request->has('origin')) {
            $query->byOrigin($request->origin);
        }

        if ($request->has('priority')) {
            $query->byPriority($request->priority);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('driver', 'like', "%{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%")
                  ->orWhere('origin', 'like', "%{$search}%")
                  ->orWhere('cargo', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $shipments = $query->with(['truck', 'cargoItems', 'trackingUpdates'])->get();

        return response()->json($shipments);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created shipment
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'truck_id' => 'required|exists:transportation_fleets,id',
            'driver' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'origin' => 'required|string|max:255',
            'departure_date' => 'required|date',
            'arrival_date' => 'required|date|after:departure_date',
            'status' => ['required', Rule::in(['Scheduled', 'In Transit', 'Delivered', 'Delayed'])],
            'cargo' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0',
            'current_location' => 'nullable|string|max:255',
            'estimated_delay' => 'nullable|integer|min:0',
            'customer_contact' => 'required|string|max:255',
            'priority' => ['required', Rule::in(['Low', 'Medium', 'High'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $shipment = TransportationShipmentTracking::create($validator->validated());

        // Update truck status to In Transit
        $truck = TransportationFleet::find($request->truck_id);
        if ($truck && $request->status === 'In Transit') {
            $truck->update(['status' => 'In Transit']);
        }

        return response()->json($shipment, 201);
    }

    /**
     * Display the specified shipment
     */
    public function show(TransportationShipmentTracking $transportationShipmentTracking): JsonResponse
    {
        $shipment = $transportationShipmentTracking->load(['truck', 'cargoItems', 'trackingUpdates']);
        return response()->json($shipment);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransportationShipmentTracking $transportationShipmentTracking)
    {
        //
    }

    /**
     * Update the specified shipment
     */
    public function update(Request $request, TransportationShipmentTracking $transportationShipmentTracking): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'truck_id' => 'required|exists:transportation_fleets,id',
            'driver' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'origin' => 'required|string|max:255',
            'departure_date' => 'required|date',
            'arrival_date' => 'required|date|after:departure_date',
            'status' => ['required', Rule::in(['Scheduled', 'In Transit', 'Delivered', 'Delayed'])],
            'cargo' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0',
            'current_location' => 'nullable|string|max:255',
            'estimated_delay' => 'nullable|integer|min:0',
            'customer_contact' => 'required|string|max:255',
            'priority' => ['required', Rule::in(['Low', 'Medium', 'High'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldStatus = $transportationShipmentTracking->status;
        $transportationShipmentTracking->update($validator->validated());

        // Handle truck status changes
        $truck = TransportationFleet::find($request->truck_id);
        if ($truck) {
            if ($request->status === 'Delivered' && $oldStatus !== 'Delivered') {
                $truck->update(['status' => 'Available']);
            } elseif ($request->status === 'In Transit' && $oldStatus !== 'In Transit') {
                $truck->update(['status' => 'In Transit']);
            }
        }

        return response()->json($transportationShipmentTracking);
    }

    /**
     * Remove the specified shipment
     */
    public function destroy(TransportationShipmentTracking $transportationShipmentTracking): JsonResponse
    {
        $transportationShipmentTracking->delete();
        return response()->json(['message' => 'Shipment deleted successfully']);
    }

    /**
     * Update shipment status
     */
    public function updateStatus(Request $request, TransportationShipmentTracking $transportationShipmentTracking): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['Scheduled', 'In Transit', 'Delivered', 'Delayed'])],
            'location' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldStatus = $transportationShipmentTracking->status;
        $transportationShipmentTracking->update([
            'status' => $request->status,
            'current_location' => $request->location,
        ]);

        // Create tracking update
        TransportationTrackingUpdate::create([
            'shipment_id' => $transportationShipmentTracking->id,
            'status' => $request->status,
            'location' => $request->location,
            'timestamp' => now(),
            'notes' => $request->notes,
            'updated_by' => $request->user()->name ?? 'System',
        ]);

        // Handle truck status changes
        $truck = $transportationShipmentTracking->truck;
        if ($truck) {
            if ($request->status === 'Delivered' && $oldStatus !== 'Delivered') {
                $truck->update(['status' => 'Available']);
            } elseif ($request->status === 'In Transit' && $oldStatus !== 'In Transit') {
                $truck->update(['status' => 'In Transit']);
            }
        }

        return response()->json(['message' => 'Status updated successfully']);
    }

    /**
     * Get tracking history for a shipment
     */
    public function trackingHistory(TransportationShipmentTracking $transportationShipmentTracking): JsonResponse
    {
        $trackingHistory = $transportationShipmentTracking->trackingUpdates()
            ->orderBy('timestamp', 'desc')
            ->get();

        return response()->json($trackingHistory);
    }

    /**
     * Get dashboard stats
     */
    public function dashboardStats(): JsonResponse
    {
        $stats = [
            'total_shipments' => TransportationShipmentTracking::count(),
            'active_shipments' => TransportationShipmentTracking::inTransit()->count(),
            'delayed_shipments' => TransportationShipmentTracking::delayed()->count(),
            'delivered_shipments' => TransportationShipmentTracking::delivered()->count(),
        ];

        return response()->json($stats);
    }
}
