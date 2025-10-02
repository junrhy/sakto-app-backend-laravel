<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->input('client_identifier');
        $status = $request->input('status');
        $date = $request->input('date');
        $patientId = $request->input('patient_id');

        $query = Appointment::byClient($clientIdentifier)->with('patient');

        if ($status) {
            $query->byStatus($status);
        }

        if ($date) {
            $query->whereDate('appointment_date', $date);
        }

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        $appointments = $query->orderByPriority()->get();

        return response()->json([
            'success' => true,
            'appointments' => $appointments
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'appointment_date' => 'required|date|after:now',
            'appointment_time' => 'required|string',
            'appointment_type' => 'required|string|in:consultation,follow_up,emergency,checkup,procedure',
            'notes' => 'nullable|string',
            'doctor_name' => 'nullable|string',
            'fee' => 'nullable|numeric|min:0',
            'client_identifier' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get patient details
        $patient = Patient::find($request->patient_id);
        
        $appointment = new Appointment([
            'client_identifier' => $request->client_identifier,
            'patient_id' => $request->patient_id,
            'patient_name' => $patient->name,
            'patient_phone' => $patient->phone,
            'patient_email' => $patient->email,
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
            'appointment_type' => $request->appointment_type,
            'notes' => $request->notes,
            'doctor_name' => $request->doctor_name,
            'fee' => $request->fee,
            'status' => 'scheduled',
            'payment_status' => 'pending'
        ]);

        // Set VIP priority based on patient
        $appointment->setPriorityFromPatient($patient);
        $appointment->save();

        return response()->json([
            'success' => true,
            'data' => $appointment->load('patient')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $appointment = Appointment::with('patient')->find($id);
        
        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $appointment
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $appointment = Appointment::find($id);
        
        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'appointment_date' => 'sometimes|date|after:now',
            'appointment_time' => 'sometimes|string',
            'appointment_type' => 'sometimes|string|in:consultation,follow_up,emergency,checkup,procedure',
            'notes' => 'nullable|string',
            'doctor_name' => 'nullable|string',
            'fee' => 'nullable|numeric|min:0',
            'status' => 'sometimes|string|in:scheduled,confirmed,completed,cancelled,no_show',
            'payment_status' => 'sometimes|string|in:pending,paid,partial',
            'cancellation_reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // If status is being changed to cancelled, set cancelled_at
        if ($request->has('status') && $request->status === 'cancelled' && $appointment->status !== 'cancelled') {
            $request->merge(['cancelled_at' => now()]);
        }

        $appointment->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $appointment->load('patient')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $appointment = Appointment::find($id);
        
        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found'
            ], 404);
        }

        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Appointment deleted successfully'
        ]);
    }

    /**
     * Get today's appointments
     */
    public function today(Request $request)
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $appointments = Appointment::byClient($clientIdentifier)
            ->today()
            ->with('patient')
            ->orderByPriority()
            ->get();

        return response()->json([
            'success' => true,
            'appointments' => $appointments
        ]);
    }

    /**
     * Get upcoming appointments
     */
    public function upcoming(Request $request)
    {
        $clientIdentifier = $request->input('client_identifier');
        $limit = $request->input('limit', 10);
        
        $appointments = Appointment::byClient($clientIdentifier)
            ->upcoming()
            ->with('patient')
            ->orderByPriority()
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'appointments' => $appointments
        ]);
    }

    /**
     * Get appointments by date range
     */
    public function byDateRange(Request $request)
    {
        $clientIdentifier = $request->input('client_identifier');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $appointments = Appointment::byClient($clientIdentifier)
            ->byDateRange($startDate, $endDate)
            ->with('patient')
            ->orderByPriority()
            ->get();

        return response()->json([
            'success' => true,
            'appointments' => $appointments
        ]);
    }

    /**
     * Update appointment status
     */
    public function updateStatus(Request $request, string $id)
    {
        $appointment = Appointment::find($id);
        
        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:scheduled,confirmed,completed,cancelled,no_show',
            'cancellation_reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = ['status' => $request->status];
        
        if ($request->status === 'cancelled') {
            $updateData['cancelled_at'] = now();
            if ($request->cancellation_reason) {
                $updateData['cancellation_reason'] = $request->cancellation_reason;
            }
        }

        $appointment->update($updateData);

        return response()->json([
            'success' => true,
            'data' => $appointment->load('patient')
        ]);
    }

    /**
     * Update appointment payment status
     */
    public function updatePaymentStatus(Request $request, string $id)
    {
        $appointment = Appointment::find($id);
        
        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|string|in:pending,paid,partial'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $appointment->update(['payment_status' => $request->payment_status]);

        return response()->json([
            'success' => true,
            'data' => $appointment->load('patient')
        ]);
    }

    /**
     * Get appointment statistics for dashboard widget
     */
    public function getStats(Request $request)
    {
        try {
            $clientIdentifier = $request->input('client_identifier');
            
            if (!$clientIdentifier) {
                return response()->json(['error' => 'Client identifier is required'], 400);
            }

            // Get today's appointments - cast appointment_date to date for PostgreSQL
            $todayAppointments = Appointment::byClient($clientIdentifier)
                ->whereRaw('CAST(appointment_date AS DATE) = ?', [today()])
                ->count();
            
            // Get upcoming appointments (next 7 days) - cast appointment_date to date for PostgreSQL
            $upcomingAppointments = Appointment::byClient($clientIdentifier)
                ->whereRaw('CAST(appointment_date AS DATE) > ?', [today()])
                ->whereRaw('CAST(appointment_date AS DATE) <= ?', [today()->addDays(7)])
                ->count();
            
            // Get completed appointments today - cast appointment_date to date for PostgreSQL
            $completedToday = Appointment::byClient($clientIdentifier)
                ->whereRaw('CAST(appointment_date AS DATE) = ?', [today()])
                ->where('status', 'completed')
                ->count();
            
            // Get cancelled appointments today - cast appointment_date to date for PostgreSQL
            $cancelledToday = Appointment::byClient($clientIdentifier)
                ->whereRaw('CAST(appointment_date AS DATE) = ?', [today()])
                ->where('status', 'cancelled')
                ->count();
            
            return response()->json([
                'today_appointments' => $todayAppointments,
                'upcoming_appointments' => $upcomingAppointments,
                'completed_today' => $completedToday,
                'cancelled_today' => $cancelledToday
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to fetch appointment statistics', [
                'error' => $e->getMessage(),
                'client_identifier' => $request->input('client_identifier')
            ]);
            
            return response()->json(['error' => 'Failed to fetch appointment statistics'], 500);
        }
    }
}
