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
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'is_full_day' => 'boolean',
            'reason' => 'nullable|string|max:255',
            'client_identifier' => 'required|string'
        ]);

        // Set default values
        $validated['is_full_day'] = $validated['is_full_day'] ?? true;
        
        // If not full day, require start and end times
        if (!$validated['is_full_day']) {
            if (empty($validated['start_time']) || empty($validated['end_time'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Start time and end time are required for time range blocking'
                ], 400);
            }
        }

        // Check for overlapping blocks
        $query = FnbBlockedDate::where('client_identifier', $validated['client_identifier'])
            ->where('blocked_date', $validated['blocked_date']);

        if ($validated['is_full_day']) {
            // Check if any block exists for this date (full day or time range)
            $existingBlock = $query->first();
        } else {
            // Check for overlapping time ranges
            $existingBlock = $query->where(function($q) use ($validated) {
                $q->where('is_full_day', true) // Full day blocks overlap with any time range
                  ->orWhere(function($subQ) use ($validated) {
                      $subQ->where('is_full_day', false)
                           ->where(function($timeQ) use ($validated) {
                               // Check if the new time range overlaps with existing time ranges
                               $timeQ->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                                     ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                                     ->orWhere(function($overlapQ) use ($validated) {
                                         $overlapQ->where('start_time', '<=', $validated['start_time'])
                                                  ->where('end_time', '>=', $validated['end_time']);
                                     });
                           });
                  });
            })->first();
        }

        if ($existingBlock) {
            return response()->json([
                'status' => 'error',
                'message' => 'This time period is already blocked or overlaps with an existing block'
            ], 400);
        }

        $blockedDate = FnbBlockedDate::create($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Time period blocked successfully',
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
        $validated = $request->validate([
            'blocked_date' => 'required|date|after_or_equal:today',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'is_full_day' => 'boolean',
            'reason' => 'nullable|string|max:255'
        ]);

        $blockedDate = FnbBlockedDate::findOrFail($id);
        
        // Set default values
        $validated['is_full_day'] = $validated['is_full_day'] ?? true;
        
        // If not full day, require start and end times
        if (!$validated['is_full_day']) {
            if (empty($validated['start_time']) || empty($validated['end_time'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Start time and end time are required for time range blocking'
                ], 400);
            }
        }

        // Check for overlapping blocks (excluding current record)
        $query = FnbBlockedDate::where('client_identifier', $blockedDate->client_identifier)
            ->where('blocked_date', $validated['blocked_date'])
            ->where('id', '!=', $id);

        if ($validated['is_full_day']) {
            // Check if any block exists for this date (full day or time range)
            $existingBlock = $query->first();
        } else {
            // Check for overlapping time ranges
            $existingBlock = $query->where(function($q) use ($validated) {
                $q->where('is_full_day', true) // Full day blocks overlap with any time range
                  ->orWhere(function($subQ) use ($validated) {
                      $subQ->where('is_full_day', false)
                           ->where(function($timeQ) use ($validated) {
                               // Check if the new time range overlaps with existing time ranges
                               $timeQ->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                                     ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                                     ->orWhere(function($overlapQ) use ($validated) {
                                         $overlapQ->where('start_time', '<=', $validated['start_time'])
                                                  ->where('end_time', '>=', $validated['end_time']);
                                     });
                           });
                  });
            })->first();
        }

        if ($existingBlock) {
            return response()->json([
                'status' => 'error',
                'message' => 'This time period is already blocked or overlaps with an existing block'
            ], 400);
        }

        $blockedDate->update($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Blocked time period updated successfully',
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
            // Check if specific time is blocked
            $isBlocked = $query->where(function($q) use ($validated) {
                $q->where('is_full_day', true) // Full day blocks
                  ->orWhere(function($subQ) use ($validated) {
                      $subQ->where('is_full_day', false)
                           ->where('start_time', '<=', $validated['time'])
                           ->where('end_time', '>', $validated['time']);
                  });
            })->exists();
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
}
