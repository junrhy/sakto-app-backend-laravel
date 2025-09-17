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
        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'arn' => 'nullable|string|max:255|unique:patients,arn',
            'birthdate' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'client_identifier' => 'required|string'
        ]);

        // Generate ARN if not provided
        if (empty($validated['arn'])) {
            $validated['arn'] = $this->generateArn($validated['client_identifier']);
        }

        $patient = Patient::create($validated);
        
        // Create default dental chart records for the new patient
        $this->createDefaultDentalChart($patient->id);
        
        return response()->json(['data' => $patient], 201);
    }

    /**
     * Generate a unique ARN for a patient
     */
    private function generateArn($clientIdentifier)
    {
        $date = now()->format('Ymd');
        $prefix = strtoupper(substr($clientIdentifier, 0, 3));
        
        // Get the count of patients for this client today
        $count = Patient::where('client_identifier', $clientIdentifier)
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;
        
        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$date}-{$sequence}";
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
        
        // Validate the request
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'arn' => 'sometimes|nullable|string|max:255|unique:patients,arn,' . $patient->id,
            'birthdate' => 'sometimes|nullable|string',
            'phone' => 'sometimes|nullable|string',
            'email' => 'sometimes|nullable|email'
        ]);

        $patient->update($validated);
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
