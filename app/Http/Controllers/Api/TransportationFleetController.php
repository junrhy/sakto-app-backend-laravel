<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransportationFleet;
use App\Models\TransportationFuelUpdate;
use App\Models\TransportationMaintenanceRecord;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransportationFleetController extends Controller
{
    /**
     * Display a listing of trucks
     */
    public function index(Request $request): JsonResponse
    {
        $query = TransportationFleet::query();

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('driver')) {
            $query->byDriver($request->driver);
        }

        if ($request->has('plate_number')) {
            $query->byPlateNumber($request->plate_number);
        }

        if ($request->has('model')) {
            $query->byModel($request->model);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('driver', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $trucks = $query->with(['shipments', 'fuelUpdates', 'maintenanceRecords'])->get();

        return response()->json($trucks);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created truck
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plate_number' => 'required|string|max:255|unique:transportation_fleets',
            'model' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'status' => ['required', Rule::in(['Available', 'In Transit', 'Maintenance'])],
            'last_maintenance' => 'nullable|date',
            'fuel_level' => 'nullable|numeric|min:0|max:100',
            'mileage' => 'nullable|integer|min:0',
            'driver' => 'nullable|string|max:255',
            'driver_contact' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $truck = TransportationFleet::create($validator->validated());

        return response()->json($truck, 201);
    }

    /**
     * Display the specified truck
     */
    public function show(TransportationFleet $transportationFleet): JsonResponse
    {
        $truck = $transportationFleet->load(['shipments', 'fuelUpdates', 'maintenanceRecords']);
        return response()->json($truck);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransportationFleet $transportationFleet)
    {
        //
    }

    /**
     * Update the specified truck
     */
    public function update(Request $request, TransportationFleet $transportationFleet): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plate_number' => ['required', 'string', 'max:255', Rule::unique('transportation_fleets')->ignore($transportationFleet->id)],
            'model' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'status' => ['required', Rule::in(['Available', 'In Transit', 'Maintenance'])],
            'last_maintenance' => 'nullable|date',
            'fuel_level' => 'nullable|numeric|min:0|max:100',
            'mileage' => 'nullable|integer|min:0',
            'driver' => 'nullable|string|max:255',
            'driver_contact' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $transportationFleet->update($validator->validated());

        return response()->json($transportationFleet);
    }

    /**
     * Remove the specified truck
     */
    public function destroy(TransportationFleet $transportationFleet): JsonResponse
    {
        $transportationFleet->delete();
        return response()->json(['message' => 'Truck deleted successfully']);
    }

    /**
     * Update fuel level for a truck
     */
    public function updateFuel(Request $request, TransportationFleet $transportationFleet): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'liters_added' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'location' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $previousLevel = $transportationFleet->fuel_level;
        $litersAdded = $request->liters_added;
        $newLevel = min(100, $previousLevel + ($litersAdded / $transportationFleet->capacity) * 100);

        // Update truck fuel level
        $transportationFleet->update(['fuel_level' => $newLevel]);

        // Create fuel update record
        TransportationFuelUpdate::create([
            'truck_id' => $transportationFleet->id,
            'timestamp' => now(),
            'previous_level' => $previousLevel,
            'new_level' => $newLevel,
            'liters_added' => $litersAdded,
            'cost' => $request->cost,
            'location' => $request->location,
            'updated_by' => $request->user()->name ?? 'System',
        ]);

        return response()->json(['message' => 'Fuel level updated successfully', 'new_level' => $newLevel]);
    }

    /**
     * Schedule maintenance for a truck
     */
    public function scheduleMaintenance(Request $request, TransportationFleet $transportationFleet): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in(['Routine', 'Repair'])],
            'description' => 'required|string',
            'cost' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update truck status to maintenance
        $transportationFleet->update(['status' => 'Maintenance']);

        // Create maintenance record
        TransportationMaintenanceRecord::create([
            'truck_id' => $transportationFleet->id,
            'date' => now(),
            'type' => $request->type,
            'description' => $request->description,
            'cost' => $request->cost,
        ]);

        return response()->json(['message' => 'Maintenance scheduled successfully']);
    }

    /**
     * Get fuel history for a truck
     */
    public function fuelHistory(TransportationFleet $transportationFleet): JsonResponse
    {
        $fuelHistory = $transportationFleet->fuelUpdates()
            ->orderBy('timestamp', 'desc')
            ->get();

        return response()->json($fuelHistory);
    }

    /**
     * Get maintenance history for a truck
     */
    public function maintenanceHistory(TransportationFleet $transportationFleet): JsonResponse
    {
        $maintenanceHistory = $transportationFleet->maintenanceRecords()
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($maintenanceHistory);
    }

    /**
     * Get dashboard stats
     */
    public function dashboardStats(): JsonResponse
    {
        $stats = [
            'total_trucks' => TransportationFleet::count(),
            'available_trucks' => TransportationFleet::available()->count(),
            'in_transit_trucks' => TransportationFleet::inTransit()->count(),
            'maintenance_trucks' => TransportationFleet::inMaintenance()->count(),
        ];

        return response()->json($stats);
    }
}
