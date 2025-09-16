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
        
        // Transform dental chart data for frontend
        $patients = $patients->map(function ($patient) {
            // If patient has no dental chart records, create them
            if ($patient->dentalChart->isEmpty()) {
                $this->createDefaultDentalChart($patient->id);
                // Reload the relationship
                $patient->load('dentalChart');
            }
            
            $dentalChart = $patient->dentalChart->map(function ($tooth) {
                return [
                    'id' => (int) $tooth->tooth_id,
                    'status' => $tooth->status
                ];
            })->toArray();
            
            // Ensure we have exactly 32 teeth (1-32)
            $fullDentalChart = [];
            for ($i = 1; $i <= 32; $i++) {
                $existingTooth = collect($dentalChart)->firstWhere('id', $i);
                $fullDentalChart[] = $existingTooth ?: [
                    'id' => $i,
                    'status' => 'healthy'
                ];
            }
            
            $patient->dental_chart = $fullDentalChart;
            return $patient;
        });
        
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
        
        // Create default dental chart records for the new patient
        $this->createDefaultDentalChart($patient->id);
        
        return response()->json(['data' => $patient], 201);
    }

    /**
     * Create default dental chart records for a patient
     */
    private function createDefaultDentalChart($patientId)
    {
        for ($i = 1; $i <= 32; $i++) {
            \App\Models\PatientDentalChart::create([
                'patient_id' => $patientId,
                'tooth_id' => $i,
                'status' => 'healthy',
                'notes' => null
            ]);
        }
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
