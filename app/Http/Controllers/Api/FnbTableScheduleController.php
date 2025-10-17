<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbTableSchedule;
use Illuminate\Http\Request;

class FnbTableScheduleController extends Controller
{
    /**
     * Get table schedules for a specific date and client
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $date = $request->date; // Optional: filter by specific date
        
        $query = FnbTableSchedule::where('client_identifier', $clientIdentifier)
            ->with('table');
        
        if ($date) {
            $query->where('schedule_date', $date);
        }
        
        $schedules = $query->orderBy('schedule_date')->orderBy('table_id')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $schedules
        ]);
    }

    /**
     * Store a new table schedule
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string',
            'table_id' => 'required|exists:fnb_tables,id',
            'schedule_date' => 'required|date',
            'timeslots' => 'required|array|min:1',
            'timeslots.*' => 'required|date_format:H:i',
            'status' => 'required|in:available,unavailable,joined',
            'joined_with' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $schedule = FnbTableSchedule::create($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Table schedule created successfully',
            'data' => $schedule->load('table')
        ], 201);
    }

    /**
     * Update a table schedule
     */
    public function update(Request $request, string $id)
    {
        $schedule = FnbTableSchedule::findOrFail($id);
        
        $validated = $request->validate([
            'timeslots' => 'sometimes|array|min:1',
            'timeslots.*' => 'required|date_format:H:i',
            'status' => 'sometimes|in:available,unavailable,joined',
            'joined_with' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $schedule->update($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Table schedule updated successfully',
            'data' => $schedule->load('table')
        ]);
    }

    /**
     * Remove a table schedule
     */
    public function destroy(string $id)
    {
        $schedule = FnbTableSchedule::findOrFail($id);
        $schedule->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Table schedule deleted successfully'
        ]);
    }

    /**
     * Get table availability for a specific date and time
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
        ]);

        $unavailableTableIds = FnbTableSchedule::where('client_identifier', $validated['client_identifier'])
            ->where('schedule_date', $validated['date'])
            ->where('status', 'unavailable')
            ->whereJsonContains('timeslots', $validated['time'])
            ->pluck('table_id');

        return response()->json([
            'status' => 'success',
            'data' => [
                'unavailable_table_ids' => $unavailableTableIds
            ]
        ]);
    }

    /**
     * Bulk set table availability for multiple tables and timeslots
     */
    public function bulkSetAvailability(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string',
            'table_ids' => 'required|array|min:1',
            'table_ids.*' => 'required|exists:fnb_tables,id',
            'schedule_date' => 'required|date',
            'timeslots' => 'required|array|min:1',
            'timeslots.*' => 'required|date_format:H:i',
            'status' => 'required|in:available,unavailable,joined',
            'joined_with' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $created = [];
        
        foreach ($validated['table_ids'] as $tableId) {
            // Check if schedule already exists
            $existing = FnbTableSchedule::where('client_identifier', $validated['client_identifier'])
                ->where('table_id', $tableId)
                ->where('schedule_date', $validated['schedule_date'])
                ->first();

            // If status is 'available', delete the schedule (default state doesn't need a record)
            if ($validated['status'] === 'available' && $existing) {
                $existing->delete();
                continue;
            }

            // Skip creating 'available' schedules (not needed)
            if ($validated['status'] === 'available') {
                continue;
            }

            if ($existing) {
                // Update existing schedule
                $existing->update([
                    'timeslots' => $validated['timeslots'],
                    'status' => $validated['status'],
                    'joined_with' => $validated['joined_with'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
                $created[] = $existing;
            } else {
                // Create new schedule
                $created[] = FnbTableSchedule::create([
                    'client_identifier' => $validated['client_identifier'],
                    'table_id' => $tableId,
                    'schedule_date' => $validated['schedule_date'],
                    'timeslots' => $validated['timeslots'],
                    'status' => $validated['status'],
                    'joined_with' => $validated['joined_with'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Table schedules updated successfully',
            'data' => $created
        ]);
    }
}
