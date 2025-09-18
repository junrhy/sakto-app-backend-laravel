<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientDiagnosis;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PatientDiagnosisController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $patientId = $request->input('patient_id');
        $encounterId = $request->input('encounter_id');

        $query = PatientDiagnosis::forClient($clientIdentifier)
            ->with(['patient', 'encounter'])
            ->orderBy('diagnosis_date', 'desc');

        if ($patientId) $query->where('patient_id', $patientId);
        if ($encounterId) $query->where('encounter_id', $encounterId);
        if ($request->has('status')) $query->byStatus($request->input('status'));
        if ($request->has('diagnosis_type')) $query->byType($request->input('diagnosis_type'));

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate(20)
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'patient_id' => 'required|exists:patients,id',
            'encounter_id' => 'nullable|exists:patient_encounters,id',
            'diagnosis_name' => 'required|string',
            'diagnosis_description' => 'nullable|string',
            'icd10_code' => 'nullable|string',
            'diagnosis_type' => 'required|in:primary,secondary,differential,rule_out,provisional,confirmed',
            'category' => 'required|in:acute,chronic,resolved,recurring,unknown',
            'diagnosis_date' => 'required|date',
            'onset_date' => 'nullable|date',
            'diagnosed_by' => 'nullable|string',
            'severity' => 'required|in:mild,moderate,severe,critical,unknown',
            'status' => 'required|in:active,resolved,in_remission,recurrent,inactive',
            'clinical_notes' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'next_review_date' => 'nullable|date',
            'requires_monitoring' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $diagnosis = PatientDiagnosis::create($validator->validated());
        $diagnosis->load(['patient', 'encounter']);

        return response()->json([
            'status' => 'success',
            'message' => 'Diagnosis created successfully',
            'data' => $diagnosis
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $diagnosis = PatientDiagnosis::forClient($clientIdentifier)
            ->with(['patient', 'encounter'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $diagnosis
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $diagnosis = PatientDiagnosis::forClient($clientIdentifier)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'diagnosis_name' => 'sometimes|string',
            'diagnosis_description' => 'nullable|string',
            'icd10_code' => 'nullable|string',
            'diagnosis_type' => 'sometimes|in:primary,secondary,differential,rule_out,provisional,confirmed',
            'category' => 'sometimes|in:acute,chronic,resolved,recurring,unknown',
            'severity' => 'sometimes|in:mild,moderate,severe,critical,unknown',
            'status' => 'sometimes|in:active,resolved,in_remission,recurrent,inactive',
            'clinical_notes' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'resolution_date' => 'nullable|date',
            'next_review_date' => 'nullable|date',
            'requires_monitoring' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $diagnosis->update($validator->validated());
        $diagnosis->load(['patient', 'encounter']);

        return response()->json([
            'status' => 'success',
            'message' => 'Diagnosis updated successfully',
            'data' => $diagnosis
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $diagnosis = PatientDiagnosis::forClient($clientIdentifier)->findOrFail($id);
        $diagnosis->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Diagnosis deleted successfully'
        ]);
    }

    public function active(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $patientId = $request->input('patient_id');

        $query = PatientDiagnosis::forClient($clientIdentifier)
            ->active()
            ->with(['patient'])
            ->orderBySeverity()
            ->orderByType();

        if ($patientId) $query->where('patient_id', $patientId);

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }

    public function chronic(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $patientId = $request->input('patient_id');

        $query = PatientDiagnosis::forClient($clientIdentifier)
            ->chronic()
            ->with(['patient'])
            ->orderBy('diagnosis_date', 'desc');

        if ($patientId) $query->where('patient_id', $patientId);

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }
}