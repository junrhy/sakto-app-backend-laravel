<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->input('client_identifier');
        $patients = Patient::where('client_identifier', $clientIdentifier)->with('bills', 'payments', 'checkups', 'dentalChart')->get();
        return response()->json([
            'success' => true,
            'patients' => $patients,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $patient = Patient::create($request->all());
        
        return response()->json(['data' => $patient], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient)
    {
        return response()->json(['data' => $patient]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $patient = Patient::find($request->id);
        $patient->update($request->all());
        return response()->json(['data' => $patient]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $patient = Patient::find($request->id);
        $patient->delete();
        return response()->json(['data' => $patient]);
    }

    public function updateNextVisit(Request $request)
    {
        $patient = Patient::find($request->id);
        $patient->next_visit_date = $request->next_visit_date;
        $patient->save();
        return response()->json(['data' => $patient]);
    }
}
