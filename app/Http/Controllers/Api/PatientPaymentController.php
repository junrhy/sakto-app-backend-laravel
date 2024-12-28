<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientPayment;
use Illuminate\Http\Request;

class PatientPaymentController extends Controller
{
    public function getPayments($patientId)
    {
        $patientPayments = PatientPayment::where('patient_id', $patientId)->get();
        return response()->json([
            'success' => true,
            'payments' => $patientPayments
        ]);
    }

    public function store(Request $request)
    {
        try {
            $patientPayment = PatientPayment::create($request->all());
            return response()->json([
                'success' => true,
                'payment' => $patientPayment
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($patientId, $id)
    {
        try {
            $patientPayment = PatientPayment::findOrFail($id);
            $patientPayment->delete();
            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
