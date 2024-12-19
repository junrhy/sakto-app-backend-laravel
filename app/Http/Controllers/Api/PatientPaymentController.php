<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientPayment;
use Illuminate\Http\Request;

class PatientPaymentController extends Controller
{
    public function store(Request $request)
    {
        $patientPayment = PatientPayment::create($request->all());
        return response()->json($patientPayment, 201);
    }

    public function destroy($id)
    {
        $patientPayment = PatientPayment::findOrFail($id);
        $patientPayment->delete();
        return response()->json(['message' => 'Payment deleted successfully']);
    }
}
