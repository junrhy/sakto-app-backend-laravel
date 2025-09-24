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
        // Validate the request with comprehensive field validation
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'arn' => 'nullable|string|max:255|unique:patients,arn',
            'birthdate' => 'nullable|date',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'gender' => 'nullable|string|in:male,female,other,prefer_not_to_say',
            'blood_type' => 'nullable|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'preferred_language' => 'nullable|string|max:100',
            'smoking_status' => 'nullable|string|in:never,former,current,unknown',
            'alcohol_use' => 'nullable|string|in:never,occasional,moderate,heavy,unknown',
            'occupation' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive,deceased',
            'medical_history' => 'nullable|string',
            'allergies' => 'nullable|string',
            'medications' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_email' => 'nullable|email|max:255',
            'emergency_contact_address' => 'nullable|string|max:500',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_policy_number' => 'nullable|string|max:100',
            'insurance_expiration_date' => 'nullable|date',
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

    /**
     * Update patient VIP status
     */
    public function updateVipStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'is_vip' => 'required|boolean',
                'vip_tier' => 'nullable|in:standard,gold,platinum,diamond',
                'vip_discount_percentage' => 'nullable|numeric|min:0|max:100',
                'vip_notes' => 'nullable|string',
                'priority_scheduling' => 'nullable|boolean',
                'extended_consultation_time' => 'nullable|boolean',
                'dedicated_staff_assignment' => 'nullable|boolean',
                'complimentary_services' => 'nullable|boolean',
                'client_identifier' => 'required|string'
            ]);

            $patient = Patient::where('id', $id)
                ->where('client_identifier', $validated['client_identifier'])
                ->firstOrFail();

            // If removing VIP status, clear all VIP fields
            if (!$validated['is_vip']) {
                $patient->removeVipStatus();
            } else {
                // Update VIP status
                $patient->is_vip = $validated['is_vip'];
                $patient->vip_tier = $validated['vip_tier'] ?? 'gold';
                $patient->vip_discount_percentage = $validated['vip_discount_percentage'] ?? 0;
                $patient->vip_notes = $validated['vip_notes'] ?? null;
                $patient->priority_scheduling = $validated['priority_scheduling'] ?? false;
                $patient->extended_consultation_time = $validated['extended_consultation_time'] ?? false;
                $patient->dedicated_staff_assignment = $validated['dedicated_staff_assignment'] ?? false;
                $patient->complimentary_services = $validated['complimentary_services'] ?? false;
                
                // Set VIP since date if this is the first time being set as VIP
                if (!$patient->vip_since) {
                    $patient->vip_since = now();
                }
                
                $patient->save();
            }

            // Load fresh patient data with VIP tier display information
            $patient->refresh();
            $patient->vip_tier_display = $patient->vip_tier_display;

            return response()->json([
                'success' => true,
                'message' => 'VIP status updated successfully',
                'patient' => $patient
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update VIP status: ' . $e->getMessage()
            ], 500);
        }
    }
}
