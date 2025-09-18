<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientMedication;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PatientMedicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $patientId = $request->input('patient_id');

        $query = PatientMedication::forClient($clientIdentifier)
            ->with(['patient'])
            ->orderBy('start_date', 'desc');

        if ($patientId) $query->where('patient_id', $patientId);
        if ($request->has('status')) $query->where('status', $request->input('status'));
        if ($request->has('medication_type')) $query->byType($request->input('medication_type'));
        if ($request->boolean('current_only')) $query->current();

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
            'medication_name' => 'required|string',
            'generic_name' => 'nullable|string',
            'brand_name' => 'nullable|string',
            'strength' => 'nullable|string',
            'dosage_form' => 'nullable|string',
            'dosage' => 'nullable|string',
            'frequency' => 'nullable|string',
            'route' => 'nullable|string',
            'instructions' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'duration_days' => 'nullable|integer|min:1',
            'as_needed' => 'boolean',
            'indication' => 'nullable|string',
            'prescribed_by' => 'nullable|string',
            'prescription_date' => 'nullable|date',
            'refills_remaining' => 'nullable|integer|min:0',
            'status' => 'required|in:active,discontinued,completed,on_hold,cancelled',
            'medication_type' => 'required|in:prescription,over_the_counter,supplement,herbal,other',
            'adherence' => 'nullable|in:excellent,good,fair,poor,unknown'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $medication = PatientMedication::create($validator->validated());
        $medication->load(['patient']);

        return response()->json([
            'status' => 'success',
            'message' => 'Medication recorded successfully',
            'data' => $medication
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $medication = PatientMedication::forClient($clientIdentifier)
            ->with(['patient'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $medication
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $medication = PatientMedication::forClient($clientIdentifier)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'medication_name' => 'sometimes|string',
            'dosage' => 'nullable|string',
            'frequency' => 'nullable|string',
            'instructions' => 'nullable|string',
            'end_date' => 'nullable|date',
            'status' => 'sometimes|in:active,discontinued,completed,on_hold,cancelled',
            'refills_remaining' => 'nullable|integer|min:0',
            'adherence' => 'nullable|in:excellent,good,fair,poor,unknown',
            'side_effects_experienced' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $medication->update($validator->validated());
        $medication->load(['patient']);

        return response()->json([
            'status' => 'success',
            'message' => 'Medication updated successfully',
            'data' => $medication
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $medication = PatientMedication::forClient($clientIdentifier)->findOrFail($id);
        $medication->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Medication deleted successfully'
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $patientId = $request->input('patient_id');

        $query = PatientMedication::forClient($clientIdentifier)
            ->current()
            ->with(['patient'])
            ->orderBy('medication_name');

        if ($patientId) $query->where('patient_id', $patientId);

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }
}