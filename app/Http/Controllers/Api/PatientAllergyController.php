<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientAllergy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PatientAllergyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $patientId = $request->input('patient_id');

        $query = PatientAllergy::forClient($clientIdentifier)
            ->with(['patient'])
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc');

        if ($patientId) $query->where('patient_id', $patientId);
        if ($request->has('allergen_type')) $query->byType($request->input('allergen_type'));
        if ($request->has('severity')) $query->bySeverity($request->input('severity'));
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
            'allergen' => 'required|string',
            'allergen_type' => 'required|in:medication,food,environmental,latex,contrast_dye,other',
            'reaction_description' => 'required|string',
            'severity' => 'required|in:mild,moderate,severe,life_threatening,unknown',
            'symptoms' => 'nullable|array',
            'first_occurrence_date' => 'nullable|date',
            'last_occurrence_date' => 'nullable|date',
            'onset_time' => 'nullable|string',
            'status' => 'required|in:active,inactive,resolved',
            'verification_status' => 'required|in:confirmed,unconfirmed,patient_reported,family_reported',
            'notes' => 'nullable|string',
            'reported_by' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $allergy = PatientAllergy::create($validator->validated());
        $allergy->load(['patient']);

        return response()->json([
            'status' => 'success',
            'message' => 'Allergy recorded successfully',
            'data' => $allergy
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $allergy = PatientAllergy::forClient($clientIdentifier)
            ->with(['patient'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $allergy
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $allergy = PatientAllergy::forClient($clientIdentifier)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'allergen' => 'sometimes|string',
            'allergen_type' => 'sometimes|in:medication,food,environmental,latex,contrast_dye,other',
            'reaction_description' => 'sometimes|string',
            'severity' => 'sometimes|in:mild,moderate,severe,life_threatening,unknown',
            'symptoms' => 'nullable|array',
            'last_occurrence_date' => 'nullable|date',
            'status' => 'sometimes|in:active,inactive,resolved',
            'verification_status' => 'sometimes|in:confirmed,unconfirmed,patient_reported,family_reported',
            'notes' => 'nullable|string',
            'verified_date' => 'nullable|datetime',
            'verified_by' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $allergy->update($validator->validated());
        $allergy->load(['patient']);

        return response()->json([
            'status' => 'success',
            'message' => 'Allergy updated successfully',
            'data' => $allergy
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $allergy = PatientAllergy::forClient($clientIdentifier)->findOrFail($id);
        $allergy->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Allergy deleted successfully'
        ]);
    }

    public function lifeThreatening(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $patientId = $request->input('patient_id');

        $query = PatientAllergy::forClient($clientIdentifier)
            ->lifeThreatening()
            ->active()
            ->with(['patient']);

        if ($patientId) $query->where('patient_id', $patientId);

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }
}