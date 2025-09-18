<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientVitalSigns;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PatientVitalSignsController extends Controller
{
    /**
     * Display a listing of vital signs for a client
     */
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $patientId = $request->input('patient_id');
        $encounterId = $request->input('encounter_id');

        $query = PatientVitalSigns::forClient($clientIdentifier)
            ->with(['patient', 'encounter'])
            ->orderBy('measured_at', 'desc');

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        if ($encounterId) {
            $query->where('encounter_id', $encounterId);
        }

        // Filter by date range if provided
        if ($request->has('date_from')) {
            $query->where('measured_at', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->where('measured_at', '<=', $request->input('date_to'));
        }

        // Filter abnormal results if requested
        if ($request->boolean('abnormal_only')) {
            $query->abnormal();
        }

        $vitalSigns = $query->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => $vitalSigns
        ]);
    }

    /**
     * Store newly created vital signs
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'patient_id' => 'required|exists:patients,id',
            'encounter_id' => 'nullable|exists:patient_encounters,id',
            'measured_at' => 'required|date',
            'systolic_bp' => 'nullable|numeric|min:0|max:300',
            'diastolic_bp' => 'nullable|numeric|min:0|max:200',
            'bp_position' => 'nullable|in:sitting,standing,lying',
            'bp_cuff_size' => 'nullable|in:pediatric,adult,large',
            'heart_rate' => 'nullable|numeric|min:0|max:300',
            'heart_rhythm' => 'nullable|in:regular,irregular',
            'respiratory_rate' => 'nullable|numeric|min:0|max:100',
            'breathing_quality' => 'nullable|in:normal,labored,shallow',
            'temperature' => 'nullable|numeric|min:25|max:50',
            'temperature_unit' => 'nullable|in:celsius,fahrenheit',
            'temperature_route' => 'nullable|in:oral,rectal,axillary,tympanic,temporal',
            'oxygen_saturation' => 'nullable|numeric|min:0|max:100',
            'on_oxygen' => 'boolean',
            'oxygen_flow_rate' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0|max:1000',
            'height' => 'nullable|numeric|min:0|max:300',
            'head_circumference' => 'nullable|numeric|min:0|max:100',
            'pain_score' => 'nullable|integer|min:0|max:10',
            'pain_location' => 'nullable|string',
            'pain_quality' => 'nullable|string',
            'glucose_level' => 'nullable|numeric|min:0|max:1000',
            'glucose_test_type' => 'nullable|in:fasting,random,post_meal',
            'measured_by' => 'nullable|string',
            'measurement_method' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vitalSigns = PatientVitalSigns::create($validator->validated());
        $vitalSigns->load(['patient', 'encounter']);

        return response()->json([
            'status' => 'success',
            'message' => 'Vital signs recorded successfully',
            'data' => $vitalSigns
        ], 201);
    }

    /**
     * Display the specified vital signs
     */
    public function show(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');

        $vitalSigns = PatientVitalSigns::forClient($clientIdentifier)
            ->with(['patient', 'encounter'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $vitalSigns
        ]);
    }

    /**
     * Update the specified vital signs
     */
    public function update(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');

        $vitalSigns = PatientVitalSigns::forClient($clientIdentifier)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'measured_at' => 'sometimes|date',
            'systolic_bp' => 'nullable|numeric|min:0|max:300',
            'diastolic_bp' => 'nullable|numeric|min:0|max:200',
            'bp_position' => 'nullable|in:sitting,standing,lying',
            'bp_cuff_size' => 'nullable|in:pediatric,adult,large',
            'heart_rate' => 'nullable|numeric|min:0|max:300',
            'heart_rhythm' => 'nullable|in:regular,irregular',
            'respiratory_rate' => 'nullable|numeric|min:0|max:100',
            'breathing_quality' => 'nullable|in:normal,labored,shallow',
            'temperature' => 'nullable|numeric|min:25|max:50',
            'temperature_unit' => 'nullable|in:celsius,fahrenheit',
            'temperature_route' => 'nullable|in:oral,rectal,axillary,tympanic,temporal',
            'oxygen_saturation' => 'nullable|numeric|min:0|max:100',
            'on_oxygen' => 'boolean',
            'oxygen_flow_rate' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0|max:1000',
            'height' => 'nullable|numeric|min:0|max:300',
            'head_circumference' => 'nullable|numeric|min:0|max:100',
            'pain_score' => 'nullable|integer|min:0|max:10',
            'pain_location' => 'nullable|string',
            'pain_quality' => 'nullable|string',
            'glucose_level' => 'nullable|numeric|min:0|max:1000',
            'glucose_test_type' => 'nullable|in:fasting,random,post_meal',
            'measured_by' => 'nullable|string',
            'measurement_method' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vitalSigns->update($validator->validated());
        $vitalSigns->load(['patient', 'encounter']);

        return response()->json([
            'status' => 'success',
            'message' => 'Vital signs updated successfully',
            'data' => $vitalSigns
        ]);
    }

    /**
     * Remove the specified vital signs
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');

        $vitalSigns = PatientVitalSigns::forClient($clientIdentifier)->findOrFail($id);
        $vitalSigns->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Vital signs deleted successfully'
        ]);
    }

    /**
     * Get vital signs trends for a patient
     */
    public function trends(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $patientId = $request->input('patient_id');
        $days = $request->input('days', 30);

        if (!$patientId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Patient ID is required for trends'
            ], 400);
        }

        $vitalSigns = PatientVitalSigns::forClient($clientIdentifier)
            ->where('patient_id', $patientId)
            ->where('measured_at', '>=', now()->subDays($days))
            ->orderBy('measured_at', 'asc')
            ->get();

        $trends = [
            'blood_pressure' => $vitalSigns->whereNotNull('systolic_bp')
                ->whereNotNull('diastolic_bp')
                ->map(function ($vs) {
                    return [
                        'date' => $vs->measured_at->format('Y-m-d'),
                        'systolic' => $vs->systolic_bp,
                        'diastolic' => $vs->diastolic_bp
                    ];
                })->values(),
            'heart_rate' => $vitalSigns->whereNotNull('heart_rate')
                ->map(function ($vs) {
                    return [
                        'date' => $vs->measured_at->format('Y-m-d'),
                        'value' => $vs->heart_rate
                    ];
                })->values(),
            'temperature' => $vitalSigns->whereNotNull('temperature')
                ->map(function ($vs) {
                    return [
                        'date' => $vs->measured_at->format('Y-m-d'),
                        'value' => $vs->temperature,
                        'unit' => $vs->temperature_unit
                    ];
                })->values(),
            'weight' => $vitalSigns->whereNotNull('weight')
                ->map(function ($vs) {
                    return [
                        'date' => $vs->measured_at->format('Y-m-d'),
                        'value' => $vs->weight,
                        'bmi' => $vs->bmi
                    ];
                })->values(),
            'oxygen_saturation' => $vitalSigns->whereNotNull('oxygen_saturation')
                ->map(function ($vs) {
                    return [
                        'date' => $vs->measured_at->format('Y-m-d'),
                        'value' => $vs->oxygen_saturation
                    ];
                })->values()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $trends
        ]);
    }

    /**
     * Get abnormal vital signs
     */
    public function abnormal(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $patientId = $request->input('patient_id');

        $query = PatientVitalSigns::forClient($clientIdentifier)
            ->abnormal()
            ->with(['patient', 'encounter'])
            ->orderBy('measured_at', 'desc');

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        $abnormalVitalSigns = $query->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $abnormalVitalSigns
        ]);
    }
}