<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientMedicalHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PatientMedicalHistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $patientId = $request->input('patient_id');

        $query = PatientMedicalHistory::forClient($clientIdentifier)
            ->with(['patient'])
            ->orderBy('date_occurred', 'desc');

        if ($patientId) $query->where('patient_id', $patientId);
        if ($request->has('type')) $query->byType($request->input('type'));
        if ($request->boolean('active_only')) $query->active();

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
            'type' => 'required|in:past_illness,surgery,hospitalization,family_history,social_history,immunization,other',
            'condition_name' => 'required|string',
            'description' => 'nullable|string',
            'date_occurred' => 'nullable|date',
            'icd10_code' => 'nullable|string',
            'family_relationship' => 'nullable|string',
            'age_at_diagnosis' => 'nullable|integer|min:0|max:150',
            'surgeon_name' => 'nullable|string',
            'hospital_name' => 'nullable|string',
            'complications' => 'nullable|string',
            'status' => 'required|in:active,resolved,chronic,unknown',
            'severity' => 'required|in:mild,moderate,severe,unknown',
            'notes' => 'nullable|string',
            'source' => 'required|in:patient_reported,medical_record,family_member'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $history = PatientMedicalHistory::create($validator->validated());
        $history->load(['patient']);

        return response()->json([
            'status' => 'success',
            'message' => 'Medical history recorded successfully',
            'data' => $history
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $history = PatientMedicalHistory::forClient($clientIdentifier)
            ->with(['patient'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $history = PatientMedicalHistory::forClient($clientIdentifier)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'condition_name' => 'sometimes|string',
            'description' => 'nullable|string',
            'date_occurred' => 'nullable|date',
            'icd10_code' => 'nullable|string',
            'status' => 'sometimes|in:active,resolved,chronic,unknown',
            'severity' => 'sometimes|in:mild,moderate,severe,unknown',
            'notes' => 'nullable|string',
            'complications' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $history->update($validator->validated());
        $history->load(['patient']);

        return response()->json([
            'status' => 'success',
            'message' => 'Medical history updated successfully',
            'data' => $history
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $history = PatientMedicalHistory::forClient($clientIdentifier)->findOrFail($id);
        $history->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Medical history deleted successfully'
        ]);
    }

    public function familyHistory(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $patientId = $request->input('patient_id');

        $query = PatientMedicalHistory::forClient($clientIdentifier)
            ->familyHistory()
            ->with(['patient'])
            ->orderBy('family_relationship')
            ->orderBy('condition_name');

        if ($patientId) $query->where('patient_id', $patientId);

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }

    public function surgeries(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $patientId = $request->input('patient_id');

        $query = PatientMedicalHistory::forClient($clientIdentifier)
            ->surgeries()
            ->with(['patient'])
            ->orderBy('date_occurred', 'desc');

        if ($patientId) $query->where('patient_id', $patientId);

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }
}