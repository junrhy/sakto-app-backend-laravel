<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbReservation;
use App\Models\FnbBlockedDate;
use Illuminate\Http\Request;

class FnbReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $reservations = FnbReservation::where('client_identifier', $clientIdentifier)->get();
        return response()->json($reservations);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required',
                'date' => 'required',
                'time' => 'required',
                'guests' => 'required',
                'table_ids' => 'nullable|array',
                'client_identifier' => 'required',
                'status' => 'required',
                'contact' => 'nullable',
                'notes' => 'nullable',
            ]);

            // Generate confirmation token
            $validated['confirmation_token'] = \Str::random(32);

            // Check if the date/time slot is blocked
            $isBlocked = FnbBlockedDate::where('client_identifier', $validated['client_identifier'])
                ->where('blocked_date', $validated['date'])
                ->whereJsonContains('timeslots', $validated['time'])
                ->exists();

            if ($isBlocked) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Reservations are not available at this time. The time period has been blocked.'
                ], 400);
            }

            $reservation = FnbReservation::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Reservation created successfully',
                'data' => $reservation
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create reservation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $fnbReservation = FnbReservation::where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'date' => 'sometimes|date',
            'time' => 'sometimes|string',
            'guests' => 'sometimes|integer|min:1',
            'table_ids' => 'nullable|array',
            'contact' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,confirmed,cancelled',
        ]);

        // If date/time is being changed, check if the new slot is blocked
        if (isset($validated['date']) || isset($validated['time'])) {
            $checkDate = $validated['date'] ?? $fnbReservation->date;
            $checkTime = $validated['time'] ?? $fnbReservation->time;
            
            $isBlocked = FnbBlockedDate::where('client_identifier', $fnbReservation->client_identifier)
                ->where('blocked_date', $checkDate)
                ->whereJsonContains('timeslots', $checkTime)
                ->exists();

            if ($isBlocked) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Reservations are not available at this time. The time period has been blocked.'
                ], 400);
            }
        }

        $fnbReservation->update($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Reservation updated successfully',
            'data' => $fnbReservation
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $fnbReservation = FnbReservation::where('id', $id)->first();
        $fnbReservation->delete();
        return response()->json(['message' => 'Reservation deleted successfully'], 204);
    }

    public function getReservationsOverview()
    {
        $reservations = FnbReservation::all();
        return response()->json($reservations);
    }

    /**
     * Get reservation by confirmation token
     */
    public function getReservationByToken($token)
    {
        try {
            $reservation = FnbReservation::where('confirmation_token', $token)->first();

            if (!$reservation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Reservation not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $reservation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch reservation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm a reservation using confirmation token
     */
    public function confirmReservation(Request $request, $token)
    {
        try {
            $reservation = FnbReservation::where('confirmation_token', $token)->first();

            if (!$reservation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid confirmation token'
                ], 404);
            }

            if ($reservation->confirmed_at) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Reservation already confirmed'
                ], 400);
            }

            // Update reservation
            $reservation->update([
                'status' => 'confirmed',
                'confirmed_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Reservation confirmed successfully',
                'data' => $reservation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to confirm reservation: ' . $e->getMessage()
            ], 500);
        }
    }
}
