<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HandymanTechnician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HandymanTechnicianController extends Controller
{
    public function index(Request $request)
    {
        $clientIdentifier = $request->query('client_identifier');

        if (!$clientIdentifier) {
            return response()->json(['error' => 'client_identifier is required'], 400);
        }

        $technicians = HandymanTechnician::where('client_identifier', $clientIdentifier)
            ->withCount(['assignments as active_assignments_count' => function ($query) {
                $query->where('assigned_end_at', '>', now())
                    ->orWhereNull('assigned_end_at');
            }])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $technicians]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'specialty' => 'nullable|string|max:255',
            'skills' => 'nullable|array',
            'status' => 'nullable|in:available,assigned,off-duty,on-leave',
            'location' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $technician = HandymanTechnician::create($validator->validated());

        return response()->json([
            'message' => 'Technician created successfully',
            'data' => $technician,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $clientIdentifier = $request->query('client_identifier');

        $technician = HandymanTechnician::where('client_identifier', $clientIdentifier)
            ->with(['assignments.task'])
            ->findOrFail($id);

        return response()->json(['data' => $technician]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'specialty' => 'nullable|string|max:255',
            'skills' => 'nullable|array',
            'status' => 'nullable|in:available,assigned,off-duty,on-leave',
            'location' => 'nullable|string|max:255',
            'current_load' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $technician = HandymanTechnician::where('client_identifier', $request->client_identifier)
            ->findOrFail($id);

        $technician->update($validator->validated());

        return response()->json([
            'message' => 'Technician updated successfully',
            'data' => $technician->fresh(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $clientIdentifier = $request->query('client_identifier') ?? $request->input('client_identifier');

        $technician = HandymanTechnician::where('client_identifier', $clientIdentifier)
            ->findOrFail($id);

        if ($technician->assignments()->exists() || $technician->workOrders()->exists()) {
            return response()->json([
                'error' => 'Cannot delete technician with existing assignments or work orders',
            ], 409);
        }

        $technician->delete();

        return response()->json(['message' => 'Technician deleted successfully']);
    }
}

