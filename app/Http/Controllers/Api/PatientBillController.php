<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientBill;
use Illuminate\Http\Request;

class PatientBillController extends Controller
{
    public function getBills($patientId)
    {
        $patientBills = PatientBill::where('patient_id', $patientId)->get();
        return response()->json([
            'success' => true,
            'bills' => $patientBills
        ]);
    }

    public function store(Request $request)
    {
        try {
            $patientBill = PatientBill::create($request->all());
            return response()->json($patientBill, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($patientId, $id)
    {
        try {
            $patientBill = PatientBill::where('patient_id', $patientId)->findOrFail($id);
            $patientBill->delete();
            return response()->json(['message' => 'Bill deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
