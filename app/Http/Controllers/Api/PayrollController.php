<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
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
}
