<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbOpenedDate;
use Illuminate\Http\Request;

class FnbOpenedDateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $openedDates = FnbOpenedDate::where('client_identifier', $clientIdentifier)
            ->orderBy('opened_date')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $openedDates
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'opened_date' => 'required|date',
            'timeslots' => 'required|array|min:1',
            'timeslots.*' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:255',
            'client_identifier' => 'required|string'
        ]);

        $openedDate = FnbOpenedDate::create($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Time slots opened successfully',
            'data' => $openedDate
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $openedDate = FnbOpenedDate::findOrFail($id);
        
        $validated = $request->validate([
            'opened_date' => 'sometimes|date',
            'timeslots' => 'sometimes|array|min:1',
            'timeslots.*' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:255',
            'client_identifier' => 'sometimes|string'
        ]);

        $openedDate->update($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Opened date updated successfully',
            'data' => $openedDate
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $openedDate = FnbOpenedDate::findOrFail($id);
        $openedDate->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Opened date removed successfully'
        ]);
    }
}
