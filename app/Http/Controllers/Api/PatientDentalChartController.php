<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientDentalChart;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientDentalChartController extends Controller
{
    /**
     * Update dental chart for a patient
     */
    public function update(Request $request)
    {
        try {
            $patientId = $request->input('patient_id');
            $dentalChartData = $request->input('dental_chart', []);

            // Validate patient exists
            $patient = Patient::find($patientId);
            if (!$patient) {
                return response()->json(['error' => 'Patient not found'], 404);
            }

            // Start transaction
            DB::beginTransaction();

            // Delete existing dental chart data
            PatientDentalChart::where('patient_id', $patientId)->delete();

            // Insert new dental chart data
            foreach ($dentalChartData as $toothData) {
                PatientDentalChart::create([
                    'patient_id' => $patientId,
                    'tooth_id' => $toothData['id'],
                    'status' => $toothData['status'],
                    'notes' => $toothData['notes'] ?? null
                ]);
            }

            DB::commit();

            // Return updated dental chart data
            $updatedDentalChart = PatientDentalChart::where('patient_id', $patientId)
                ->orderBy('tooth_id')
                ->get()
                ->map(function ($tooth) {
                    return [
                        'id' => (int) $tooth->tooth_id,
                        'status' => $tooth->status,
                        'notes' => $tooth->notes
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'dental_chart' => $updatedDentalChart
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update dental chart'], 500);
        }
    }

    /**
     * Get dental chart for a patient
     */
    public function show($patientId)
    {
        try {
            $patient = Patient::find($patientId);
            if (!$patient) {
                return response()->json(['error' => 'Patient not found'], 404);
            }

            $dentalChart = PatientDentalChart::where('patient_id', $patientId)
                ->orderBy('tooth_id')
                ->get()
                ->map(function ($tooth) {
                    return [
                        'id' => (int) $tooth->tooth_id,
                        'status' => $tooth->status,
                        'notes' => $tooth->notes
                    ];
                })
                ->toArray();

            // If no dental chart records exist, create default ones
            if (empty($dentalChart)) {
                $this->createDefaultDentalChart($patientId);
                $dentalChart = PatientDentalChart::where('patient_id', $patientId)
                    ->orderBy('tooth_id')
                    ->get()
                    ->map(function ($tooth) {
                        return [
                            'id' => (int) $tooth->tooth_id,
                            'status' => $tooth->status,
                            'notes' => $tooth->notes
                        ];
                    })
                    ->toArray();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'dental_chart' => $dentalChart
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch dental chart'], 500);
        }
    }

    /**
     * Create default dental chart records for a patient
     */
    private function createDefaultDentalChart($patientId)
    {
        for ($i = 1; $i <= 32; $i++) {
            PatientDentalChart::create([
                'patient_id' => $patientId,
                'tooth_id' => $i,
                'status' => 'healthy',
                'notes' => null
            ]);
        }
    }
}
