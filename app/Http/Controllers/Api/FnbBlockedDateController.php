<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbBlockedDate;
use Illuminate\Http\Request;

class FnbBlockedDateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $blockedDates = FnbBlockedDate::where('client_identifier', $clientIdentifier)
            ->orderBy('blocked_date')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $blockedDates
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'blocked_date' => 'required|date|after_or_equal:today',
            'timeslots' => 'required|array|min:1',
            'timeslots.*' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:255',
            'client_identifier' => 'required|string'
        ]);

        // Custom validation for overlapping time slots
        $this->validateTimeSlotsOverlap($validated);

        $blockedDate = FnbBlockedDate::create($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Time slots blocked successfully',
            'data' => $blockedDate
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $blockedDate = FnbBlockedDate::findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $blockedDate
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $blockedDate = FnbBlockedDate::findOrFail($id);
        
        $validated = $request->validate([
            'blocked_date' => 'sometimes|date|after_or_equal:today',
            'timeslots' => 'sometimes|array|min:1',
            'timeslots.*' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:255',
            'client_identifier' => 'sometimes|string'
        ]);

        // Custom validation for overlapping time slots (excluding current record)
        if (isset($validated['timeslots'])) {
            $this->validateTimeSlotsOverlap($validated, $id);
        }

        $blockedDate->update($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Blocked date updated successfully',
            'data' => $blockedDate
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $blockedDate = FnbBlockedDate::findOrFail($id);
        $blockedDate->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Blocked date removed successfully'
        ]);
    }

    /**
     * Check if a specific date/time is blocked
     */
    public function checkDate(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i',
            'client_identifier' => 'required|string'
        ]);

        $query = FnbBlockedDate::where('client_identifier', $validated['client_identifier'])
            ->where('blocked_date', $validated['date']);

        if (isset($validated['time'])) {
            // Check if specific time slot is blocked
            $isBlocked = $query->whereJsonContains('timeslots', $validated['time'])->exists();
        } else {
            // Check if any part of the date is blocked
            $isBlocked = $query->exists();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'is_blocked' => $isBlocked
            ]
        ]);
    }

    /**
     * Validate that the time slots don't overlap with existing blocks
     */
    private function validateTimeSlotsOverlap($validated, $excludeId = null)
    {
        $query = FnbBlockedDate::where('client_identifier', $validated['client_identifier'])
            ->where('blocked_date', $validated['blocked_date']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existingBlocks = $query->get();
        
        foreach ($existingBlocks as $existingBlock) {
            $existingTimeslots = $existingBlock->timeslots ?? [];
            $newTimeslots = $validated['timeslots'];
            
            // Check if any of the new time slots overlap with existing ones
            $overlappingSlots = array_intersect($existingTimeslots, $newTimeslots);
            
            if (!empty($overlappingSlots)) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    ['timeslots' => ['The following time slots are already blocked: ' . implode(', ', $overlappingSlots)]]
                );
            }
        }
    }
}
