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
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        $query = TransportationFleet::where('client_identifier', $clientIdentifier);

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

        $trucks = $query->with(['shipments', 'fuelUpdates', 'maintenanceRecords', 'bookings'])->get();

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
            'client_identifier' => 'required|string|max:255',
            'plate_number' => ['required', 'string', 'max:255', Rule::unique('transportation_fleets')->where('client_identifier', $request->client_identifier)],
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
    public function show(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }
        
        $transportationFleet = TransportationFleet::findOrFail($id);
        
        if ($transportationFleet->client_identifier !== $clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $truck = $transportationFleet->load(['shipments', 'fuelUpdates', 'maintenanceRecords', 'bookings']);
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
    public function update(Request $request, $id): JsonResponse
    {
        $transportationFleet = TransportationFleet::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'plate_number' => ['required', 'string', 'max:255', Rule::unique('transportation_fleets')->ignore($transportationFleet->id)->where('client_identifier', $request->client_identifier)],
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

        $validated = $validator->validated();
        
        // Check if client_identifier matches the resource
        if ($transportationFleet->client_identifier !== $validated['client_identifier']) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $transportationFleet->update($validated);

        return response()->json($transportationFleet);
    }

    /**
     * Remove the specified truck
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
        
        $transportationFleet = TransportationFleet::findOrFail($id);
        
        if ($transportationFleet->client_identifier !== $clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $transportationFleet->delete();
        return response()->json(['message' => 'Truck deleted successfully']);
    }

    /**
     * Update fuel level for a truck
     */
    public function updateFuel(Request $request, $id): JsonResponse
    {
        $transportationFleet = TransportationFleet::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'liters_added' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'location' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        
        // Check if client_identifier matches the resource
        if ($transportationFleet->client_identifier !== $validated['client_identifier']) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $previousLevel = $transportationFleet->fuel_level;
        $litersAdded = $validated['liters_added'];
        $newLevel = min(100, $previousLevel + ($litersAdded / $transportationFleet->capacity) * 100);

        // Update truck fuel level
        $transportationFleet->update(['fuel_level' => $newLevel]);

        // Create fuel update record
        TransportationFuelUpdate::create([
            'client_identifier' => $validated['client_identifier'],
            'truck_id' => $transportationFleet->id,
            'timestamp' => now(),
            'previous_level' => $previousLevel,
            'new_level' => $newLevel,
            'liters_added' => $litersAdded,
            'cost' => $validated['cost'],
            'location' => $validated['location'],
            'updated_by' => $request->user()->name ?? 'System',
        ]);

        return response()->json(['message' => 'Fuel level updated successfully', 'new_level' => $newLevel]);
    }

    /**
     * Schedule maintenance for a truck
     */
    public function scheduleMaintenance(Request $request, $id): JsonResponse
    {
        $transportationFleet = TransportationFleet::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'type' => ['required', Rule::in(['Routine', 'Repair'])],
            'description' => 'required|string',
            'cost' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        
        // Check if client_identifier matches the resource
        if ($transportationFleet->client_identifier !== $validated['client_identifier']) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Update truck status to maintenance
        $transportationFleet->update(['status' => 'Maintenance']);

        // Create maintenance record
        TransportationMaintenanceRecord::create([
            'client_identifier' => $validated['client_identifier'],
            'truck_id' => $transportationFleet->id,
            'date' => now(),
            'type' => $validated['type'],
            'description' => $validated['description'],
            'cost' => $validated['cost'],
        ]);

        return response()->json(['message' => 'Maintenance scheduled successfully']);
    }

    /**
     * Get fuel history for a truck
     */
    public function fuelHistory(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }
        
        $transportationFleet = TransportationFleet::findOrFail($id);
        
        if ($transportationFleet->client_identifier !== $clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $fuelHistory = $transportationFleet->fuelUpdates()
            ->orderBy('timestamp', 'desc')
            ->get();

        return response()->json($fuelHistory);
    }

    /**
     * Get maintenance history for a truck
     */
    public function maintenanceHistory(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }
        
        $transportationFleet = TransportationFleet::findOrFail($id);
        
        if ($transportationFleet->client_identifier !== $clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $maintenanceHistory = $transportationFleet->maintenanceRecords()
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($maintenanceHistory);
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

        $stats = [
            'total_trucks' => TransportationFleet::where('client_identifier', $clientIdentifier)->count(),
            'available_trucks' => TransportationFleet::where('client_identifier', $clientIdentifier)->available()->count(),
            'in_transit_trucks' => TransportationFleet::where('client_identifier', $clientIdentifier)->inTransit()->count(),
            'maintenance_trucks' => TransportationFleet::where('client_identifier', $clientIdentifier)->inMaintenance()->count(),
            'low_fuel_trucks' => TransportationFleet::where('client_identifier', $clientIdentifier)->where('fuel_level', '<', 20)->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Update GPS location for a truck
     */
    public function updateLocation(Request $request, $id): JsonResponse
    {
        $transportationFleet = TransportationFleet::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
            'speed' => 'nullable|numeric|min:0|max:300', // km/h
            'heading' => 'nullable|numeric|min:0|max:360', // degrees
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        
        // Check if client_identifier matches the resource
        if ($transportationFleet->client_identifier !== $validated['client_identifier']) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Update truck location
        $transportationFleet->update([
            'current_latitude' => $validated['latitude'],
            'current_longitude' => $validated['longitude'],
            'last_location_update' => now(),
            'current_address' => $validated['address'] ?? null,
            'speed' => $validated['speed'] ?? null,
            'heading' => $validated['heading'] ?? null,
        ]);

        return response()->json([
            'message' => 'Location updated successfully',
            'truck' => $transportationFleet->fresh()
        ]);
    }

    /**
     * Get trucks with GPS locations for map display
     */
    public function getTrucksWithLocations(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        $trucks = TransportationFleet::where('client_identifier', $clientIdentifier)
            ->withLocation()
            ->select([
                'id',
                'plate_number',
                'model',
                'status',
                'driver',
                'current_latitude',
                'current_longitude',
                'last_location_update',
                'current_address',
                'speed',
                'heading'
            ])
            ->get();

        return response()->json($trucks);
    }

    /**
     * Get real-time truck locations (for live tracking)
     */
    public function getRealTimeLocations(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        $trucks = TransportationFleet::where('client_identifier', $clientIdentifier)
            ->withRecentLocation(60) // Only trucks with location updates in last hour
            ->with(['shipments' => function ($query) {
                $query->whereIn('status', ['Scheduled', 'In Transit'])
                      ->orderBy('created_at', 'desc')
                      ->limit(1);
            }])
            ->select([
                'id',
                'plate_number',
                'model',
                'status',
                'driver',
                'current_latitude',
                'current_longitude',
                'last_location_update',
                'current_address',
                'speed',
                'heading'
            ])
            ->get()
            ->map(function ($truck) {
                $currentShipment = $truck->shipments->first();
                
                return [
                    'id' => $truck->id,
                    'plate_number' => $truck->plate_number,
                    'model' => $truck->model,
                    'status' => $truck->status,
                    'driver' => $truck->driver,
                    'location' => [
                        'latitude' => $truck->current_latitude,
                        'longitude' => $truck->current_longitude,
                        'address' => $truck->current_address,
                        'last_update' => $truck->last_location_update,
                    ],
                    'movement' => [
                        'speed' => $truck->speed,
                        'heading' => $truck->heading,
                    ],
                    'is_online' => $truck->hasRecentLocation(30), // Online if updated within 30 minutes
                    'current_shipment' => $currentShipment ? [
                        'id' => $currentShipment->id,
                        'origin' => $currentShipment->origin,
                        'destination' => $currentShipment->destination,
                        'status' => $currentShipment->status,
                        'cargo' => $currentShipment->cargo,
                        'weight' => $currentShipment->weight,
                        'departure_date' => $currentShipment->departure_date,
                        'arrival_date' => $currentShipment->arrival_date,
                    ] : null,
                ];
            });

        return response()->json($trucks);
    }

    /**
     * Get truck location history (if needed for tracking routes)
     */
    public function getLocationHistory(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }
        
        $transportationFleet = TransportationFleet::findOrFail($id);
        
        if ($transportationFleet->client_identifier !== $clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // For now, return current location. In a full implementation,
        // you might want to create a separate location_history table
        $locationHistory = [
            [
                'latitude' => $transportationFleet->current_latitude,
                'longitude' => $transportationFleet->current_longitude,
                'address' => $transportationFleet->current_address,
                'timestamp' => $transportationFleet->last_location_update,
                'speed' => $transportationFleet->speed,
                'heading' => $transportationFleet->heading,
            ]
        ];

        return response()->json($locationHistory);
    }

    /**
     * Get public list of trucks (for driver location update)
     */
    public function getPublicTrucks(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        $trucks = TransportationFleet::where('client_identifier', $clientIdentifier)
            ->select([
                'id',
                'plate_number',
                'model',
                'status',
                'driver',
                'current_latitude',
                'current_longitude',
                'last_location_update',
                'current_address',
                'speed',
                'heading'
            ])
            ->whereNotNull('driver') // Only show trucks with assigned drivers
            ->get()
            ->map(function ($truck) {
                return [
                    'id' => $truck->id,
                    'plate_number' => $truck->plate_number,
                    'model' => $truck->model,
                    'status' => $truck->status,
                    'driver' => $truck->driver,
                    'current_latitude' => $truck->current_latitude,
                    'current_longitude' => $truck->current_longitude,
                    'last_location_update' => $truck->last_location_update,
                    'current_address' => $truck->current_address,
                    'speed' => $truck->speed,
                    'heading' => $truck->heading,
                ];
            });

        return response()->json($trucks);
    }

    /**
     * Update truck location (public endpoint for drivers)
     */
    public function updateTruckLocationPublic(Request $request, $id): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
            'speed' => 'nullable|numeric|min:0|max:200',
            'heading' => 'nullable|numeric|min:0|max:360',
            'client_identifier' => 'required|string',
        ]);

        $clientIdentifier = $request->input('client_identifier');
        $truck = TransportationFleet::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->firstOrFail();

        $truck->update([
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
            'current_address' => $request->address,
            'speed' => $request->speed,
            'heading' => $request->heading,
            'last_location_update' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => [
                'id' => $truck->id,
                'plate_number' => $truck->plate_number,
                'location' => [
                    'latitude' => $truck->current_latitude,
                    'longitude' => $truck->current_longitude,
                    'address' => $truck->current_address,
                    'last_update' => $truck->last_location_update,
                ],
                'movement' => [
                    'speed' => $truck->speed,
                    'heading' => $truck->heading,
                ],
            ]
        ]);
    }
}
