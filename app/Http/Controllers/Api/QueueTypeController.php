<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QueueType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QueueTypeController extends Controller
{
    public function index(Request $request)
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $queueTypes = QueueType::where('client_identifier', $clientIdentifier)
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $queueTypes
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prefix' => 'required|string|max:10',
            'client_identifier' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $queueType = QueueType::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Queue type created successfully',
            'data' => $queueType
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $queueType = QueueType::where('client_identifier', $clientIdentifier)
            ->with(['queueNumbers' => function($query) {
                $query->whereIn('status', ['waiting', 'called', 'serving'])
                    ->orderBy('created_at');
            }])
            ->find($id);

        if (!$queueType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Queue type not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $queueType
        ]);
    }

    public function update(Request $request, $id)
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $queueType = QueueType::where('client_identifier', $clientIdentifier)->find($id);

        if (!$queueType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Queue type not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'prefix' => 'sometimes|required|string|max:10',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $queueType->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Queue type updated successfully',
            'data' => $queueType
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $queueType = QueueType::where('client_identifier', $clientIdentifier)->find($id);

        if (!$queueType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Queue type not found'
            ], 404);
        }

        $queueType->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Queue type deleted successfully'
        ]);
    }
}
