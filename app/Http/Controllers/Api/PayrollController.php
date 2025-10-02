<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\SalaryHistory;
use App\Models\PayrollPeriod;
use App\Models\TimeTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PayrollController extends Controller
{
    public function index()
    {
        $payroll = Payroll::all();
        return response()->json($payroll);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'position' => 'required|string|max:255',
            'salary' => 'required|numeric|min:0',
            'startDate' => 'required|date',
            'status' => 'required|in:active,inactive',
        ]);

        try {
            $payroll = Payroll::create($request->all());
            return response()->json($payroll, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'salary' => 'required|numeric|min:0',
            'startDate' => 'required|date',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payroll = Payroll::findOrFail($id);
        $payroll->update($request->all());
        return response()->json($payroll);
    }

    public function destroy($id)
    {
        $payroll = Payroll::findOrFail($id);
        $payroll->delete();
        return response()->json(null, 204);
    }

    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:payroll,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Payroll::whereIn('id', $request->ids)->delete();
        return response()->json(null, 204);
    }

    public function getPayrollOverview()
    {
        $totalEmployees = Payroll::count();
        $activeEmployees = Payroll::where('status', 'active')->count();
        $totalPayroll = Payroll::where('status', 'active')->sum('salary');

        return response()->json([
            'totalEmployees' => $totalEmployees,
            'activeEmployees' => $activeEmployees,
            'totalPayroll' => $totalPayroll,
        ]);
    }

    // Salary History endpoints
    public function getSalaryHistory(Request $request)
    {
        $clientIdentifier = $request->input('client_identifier');
        $salaryHistory = SalaryHistory::where('client_identifier', $clientIdentifier)
            ->orderBy('effective_date', 'desc')
            ->get();

        return response()->json($salaryHistory);
    }

    public function storeSalaryHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'employee_id' => 'required|integer',
            'previous_salary' => 'required|numeric|min:0',
            'new_salary' => 'required|numeric|min:0',
            'change_reason' => 'required|string|max:255',
            'approved_by' => 'required|string|max:255',
            'effective_date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['salary_change'] = $data['new_salary'] - $data['previous_salary'];
        $data['percentage_change'] = $data['previous_salary'] > 0 
            ? (($data['salary_change'] / $data['previous_salary']) * 100) 
            : 0;

        $salaryHistory = SalaryHistory::create($data);
        return response()->json($salaryHistory, 201);
    }

    // Payroll Periods endpoints
    public function getPayrollPeriods(Request $request)
    {
        $clientIdentifier = $request->input('client_identifier');
        $periods = PayrollPeriod::where('client_identifier', $clientIdentifier)
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json($periods);
    }

    public function storePayrollPeriod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'period_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'created_by' => 'required|string|max:255',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $period = PayrollPeriod::create($request->all());
        return response()->json($period, 201);
    }

    // Time Tracking endpoints
    public function getTimeTracking(Request $request)
    {
        $clientIdentifier = $request->input('client_identifier');
        $timeTracking = TimeTracking::where('client_identifier', $clientIdentifier)
            ->orderBy('work_date', 'desc')
            ->get();

        return response()->json($timeTracking);
    }

    public function storeTimeTracking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'employee_id' => 'required|integer',
            'work_date' => 'required|date',
            'clock_in' => 'nullable|date_format:H:i:s',
            'clock_out' => 'nullable|date_format:H:i:s',
            'status' => 'required|in:present,absent,late,half_day,leave',
            'notes' => 'nullable|string',
            'location' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        
        // Calculate hours if both clock_in and clock_out are provided
        if ($data['clock_in'] && $data['clock_out']) {
            $clockIn = \Carbon\Carbon::createFromFormat('H:i:s', $data['clock_in']);
            $clockOut = \Carbon\Carbon::createFromFormat('H:i:s', $data['clock_out']);
            $data['hours_worked'] = $clockOut->diffInHours($clockIn);
            $data['regular_hours'] = min($data['hours_worked'], 8); // Assuming 8 hours is regular
            $data['overtime_hours'] = max(0, $data['hours_worked'] - 8);
        }

        $timeTracking = TimeTracking::create($data);
        return response()->json($timeTracking, 201);
    }
}
