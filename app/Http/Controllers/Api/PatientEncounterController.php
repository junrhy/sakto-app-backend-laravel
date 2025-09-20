<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientEncounter;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PatientEncounterController extends Controller
{
    /**
     * Display a listing of encounters for a client
     */
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $patientId = $request->input('patient_id');

        $query = PatientEncounter::forClient($clientIdentifier)
            ->with(['patient', 'vitalSigns', 'diagnoses'])
            ->orderBy('encounter_datetime', 'desc');

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->byStatus($request->input('status'));
        }

        // Filter by encounter type if provided
        if ($request->has('encounter_type')) {
            $query->byType($request->input('encounter_type'));
        }

        // Filter by date range if provided
        if ($request->has('date_from')) {
            $query->where('encounter_datetime', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->where('encounter_datetime', '<=', $request->input('date_to'));
        }

        $encounters = $query->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $encounters
        ]);
    }

    /**
     * Store a newly created encounter
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'patient_id' => 'required|exists:patients,id',
            'encounter_datetime' => 'required|date',
            'encounter_type' => 'required|in:outpatient,inpatient,emergency,urgent_care,telemedicine,home_visit,consultation,follow_up,preventive_care,procedure,other',
            'encounter_class' => 'required|in:ambulatory,inpatient,emergency,home_health,virtual',
            'attending_provider' => 'required|string',
            'chief_complaint' => 'nullable|string',
            'history_present_illness' => 'nullable|string',
            'review_of_systems' => 'nullable|string',
            'physical_examination' => 'nullable|string',
            'laboratory_results' => 'nullable|string',
            'diagnostic_results' => 'nullable|string',
            'clinical_impression' => 'nullable|string',
            'differential_diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'medications_prescribed' => 'nullable|string',
            'procedures_ordered' => 'nullable|string',
            'follow_up_instructions' => 'nullable|string',
            'next_appointment_date' => 'nullable|date',
            'status' => 'required|in:scheduled,arrived,in_progress,completed,cancelled,no_show',
            'priority' => 'nullable|in:routine,urgent,emergent,stat',
            'location' => 'nullable|string',
            'room_number' => 'nullable|string',
            
            // Vital signs validation
            'vital_signs' => 'nullable|array',
            'vital_signs.systolic_bp' => 'nullable|numeric|min:0|max:300',
            'vital_signs.diastolic_bp' => 'nullable|numeric|min:0|max:200',
            'vital_signs.heart_rate' => 'nullable|numeric|min:0|max:300',
            'vital_signs.respiratory_rate' => 'nullable|numeric|min:0|max:100',
            'vital_signs.temperature' => 'nullable|numeric|min:30|max:45',
            'vital_signs.oxygen_saturation' => 'nullable|numeric|min:0|max:100',
            'vital_signs.weight' => 'nullable|numeric|min:0',
            'vital_signs.height' => 'nullable|numeric|min:0',
            'vital_signs.pain_score' => 'nullable|integer|min:0|max:10',
            'vital_signs.measured_at' => 'nullable|date',
            'vital_signs.bp_position' => 'nullable|string',
            'vital_signs.heart_rhythm' => 'nullable|string',
            'vital_signs.temperature_unit' => 'nullable|in:celsius,fahrenheit',
            'vital_signs.temperature_route' => 'nullable|string',
            'vital_signs.on_oxygen' => 'nullable|boolean',
            'vital_signs.pain_location' => 'nullable|string',
            'vital_signs.pain_quality' => 'nullable|string',
            
            // Diagnoses validation
            'diagnoses' => 'nullable|array',
            'diagnoses.*.diagnosis_name' => 'required_with:diagnoses|string',
            'diagnoses.*.diagnosis_code' => 'nullable|string',
            'diagnoses.*.diagnosis_date' => 'nullable|date',
            'diagnoses.*.severity' => 'nullable|in:mild,moderate,severe,critical,unknown',
            'diagnoses.*.status' => 'nullable|in:active,resolved,in_remission,recurrent,inactive',
            'diagnoses.*.clinical_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Generate unique encounter number
        $validated['encounter_number'] = PatientEncounter::generateEncounterNumber($validated['client_identifier']);

        // Set documentation metadata
        $validated['documented_by'] = $request->input('documented_by', 'System');
        $validated['documentation_completed_at'] = now();
        $validated['documentation_complete'] = true;

        $encounter = PatientEncounter::create($validated);

        // Handle vital signs if provided
        if ($request->has('vital_signs') && is_array($request->input('vital_signs'))) {
            $vitalSignsData = $request->input('vital_signs');
            
            // Add required fields for vital signs
            $vitalSignsData['client_identifier'] = $validated['client_identifier'];
            $vitalSignsData['patient_id'] = $validated['patient_id'];
            $vitalSignsData['encounter_id'] = $encounter->id;
            
            // Set default measured_at if not provided
            if (!isset($vitalSignsData['measured_at'])) {
                $vitalSignsData['measured_at'] = $validated['encounter_datetime'];
            }
            
            // Create the vital signs record
            $encounter->vitalSigns()->create($vitalSignsData);
        }

        // Handle diagnoses if provided
        if ($request->has('diagnoses') && is_array($request->input('diagnoses'))) {
            $diagnosesData = $request->input('diagnoses');
            
            foreach ($diagnosesData as $diagnosisData) {
                // Add required fields for each diagnosis
                $diagnosisData['client_identifier'] = $validated['client_identifier'];
                $diagnosisData['patient_id'] = $validated['patient_id'];
                $diagnosisData['encounter_id'] = $encounter->id;
                
                // Set default diagnosis_date if not provided
                if (!isset($diagnosisData['diagnosis_date'])) {
                    $diagnosisData['diagnosis_date'] = date('Y-m-d', strtotime($validated['encounter_datetime']));
                }
                
                // Create the diagnosis record
                $encounter->diagnoses()->create($diagnosisData);
            }
        }

        // Load relationships
        $encounter->load(['patient', 'vitalSigns', 'diagnoses']);

        return response()->json([
            'status' => 'success',
            'message' => 'Encounter created successfully',
            'data' => $encounter
        ], 201);
    }

    /**
     * Display the specified encounter
     */
    public function show(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');

        $encounter = PatientEncounter::forClient($clientIdentifier)
            ->with(['patient', 'vitalSigns', 'diagnoses'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $encounter
        ]);
    }

    /**
     * Update the specified encounter
     */
    public function update(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');

        $encounter = PatientEncounter::forClient($clientIdentifier)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'encounter_datetime' => 'sometimes|date',
            'encounter_type' => 'sometimes|in:outpatient,inpatient,emergency,urgent_care,telemedicine,home_visit,consultation,follow_up,preventive_care,procedure,other',
            'encounter_class' => 'sometimes|in:ambulatory,inpatient,emergency,home_health,virtual',
            'attending_provider' => 'sometimes|string',
            'chief_complaint' => 'nullable|string',
            'history_present_illness' => 'nullable|string',
            'review_of_systems' => 'nullable|string',
            'physical_examination' => 'nullable|string',
            'laboratory_results' => 'nullable|string',
            'diagnostic_results' => 'nullable|string',
            'clinical_impression' => 'nullable|string',
            'differential_diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'medications_prescribed' => 'nullable|string',
            'procedures_ordered' => 'nullable|string',
            'follow_up_instructions' => 'nullable|string',
            'next_appointment_date' => 'nullable|date',
            'status' => 'sometimes|in:scheduled,arrived,in_progress,completed,cancelled,no_show',
            'priority' => 'nullable|in:routine,urgent,emergent,stat',
            'location' => 'nullable|string',
            'room_number' => 'nullable|string',
            'end_datetime' => 'nullable|date',
            'patient_satisfaction_score' => 'nullable|integer|min:1|max:10',
            'patient_feedback' => 'nullable|string',
            'encounter_duration_minutes' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $encounter->update($validator->validated());
        $encounter->load(['patient', 'vitalSigns', 'diagnoses']);

        return response()->json([
            'status' => 'success',
            'message' => 'Encounter updated successfully',
            'data' => $encounter
        ]);
    }

    /**
     * Remove the specified encounter
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');

        $encounter = PatientEncounter::forClient($clientIdentifier)->findOrFail($id);
        $encounter->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Encounter deleted successfully'
        ]);
    }

    /**
     * Get encounter statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $patientId = $request->input('patient_id');

        $query = PatientEncounter::forClient($clientIdentifier);
        
        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        $stats = [
            'total_encounters' => $query->count(),
            'completed_encounters' => $query->byStatus('completed')->count(),
            'in_progress_encounters' => $query->byStatus('in_progress')->count(),
            'cancelled_encounters' => $query->byStatus('cancelled')->count(),
            'recent_encounters' => $query->recent(30)->count(),
            'encounter_types' => $query->selectRaw('encounter_type, COUNT(*) as count')
                ->groupBy('encounter_type')
                ->pluck('count', 'encounter_type'),
            'average_satisfaction' => $query->whereNotNull('patient_satisfaction_score')
                ->avg('patient_satisfaction_score'),
            'average_duration' => $query->whereNotNull('encounter_duration_minutes')
                ->avg('encounter_duration_minutes')
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}