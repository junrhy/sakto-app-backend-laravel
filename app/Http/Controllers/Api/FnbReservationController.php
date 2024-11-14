<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\fnbReservation;
use Illuminate\Http\Request;

class FnbReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reservations = fnbReservation::all();
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

        $reservation = fnbReservation::create($validated);
        return response()->json($reservation, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $fnbReservation = fnbReservation::where('id', $id)->first();
        $fnbReservation->delete();
        return response()->json(['message' => 'Reservation deleted successfully'], 204);
    }
}
