<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbReservation;
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
        $validated = $request->validate([
            'name' => 'required',
            'date' => 'required',
            'time' => 'required',
            'guests' => 'required',
            'table_id' => 'required',
            'client_identifier' => 'required',
            'status' => 'required',
            'contact' => 'nullable',
            'notes' => 'nullable',
        ]);

        $reservation = FnbReservation::create($validated);
        return response()->json($reservation, 201);
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
}
