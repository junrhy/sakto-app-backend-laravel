<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientCheckup;
use Illuminate\Http\Request;

class PatientCheckupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getCheckups($patientId)
    {
        $patientCheckups = PatientCheckup::where('patient_id', $patientId)->get();
        return response()->json([
            'success' => true,
            'checkups' => $patientCheckups
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $patientId)
    {
        try {
            $request->merge(['patient_id' => $patientId]);
            $patientCheckup = PatientCheckup::create($request->all());
            return response()->json([
            'success' => true,
                'checkup' => $patientCheckup
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($patientId, $checkupId)
    {
        try {
            $patientCheckup = PatientCheckup::where('patient_id', $patientId)->where('id', $checkupId)->first();
            if (!$patientCheckup) {
                    return response()->json([
                    'success' => false,
                    'error' => 'Patient checkup not found'
                ], 404);
            }
            $patientCheckup->delete();
            return response()->json([
                'success' => true,
                'message' => 'Patient checkup deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
