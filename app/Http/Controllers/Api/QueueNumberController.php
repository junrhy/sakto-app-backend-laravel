<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QueueNumber;
use App\Models\QueueType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QueueNumberController extends Controller
{
    public function index(Request $request)
    {
        $clientIdentifier = $request->input('client_identifier');
        $queueTypeId = $request->input('queue_type_id');
        $status = $request->input('status');

        $query = QueueNumber::where('client_identifier', $clientIdentifier)
            ->with('queueType');

        if ($queueTypeId) {
            $query->where('queue_type_id', $queueTypeId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $queueNumbers = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $queueNumbers
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'queue_type_id' => 'required|exists:queue_types,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_contact' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:0',
            'client_identifier' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $queueType = QueueType::where('client_identifier', $request->input('client_identifier'))
            ->find($request->input('queue_type_id'));

        if (!$queueType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Queue type not found'
            ], 404);
        }

        if (!$queueType->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Queue type is not active'
            ], 400);
        }

        $queueNumber = $queueType->getNextQueueNumber();

        $data = $request->all();
        $data['queue_number'] = $queueNumber;

        $queueNumberRecord = QueueNumber::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Queue number created successfully',
            'data' => $queueNumberRecord->load('queueType')
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $queueNumber = QueueNumber::where('client_identifier', $clientIdentifier)
            ->with('queueType')
            ->find($id);

        if (!$queueNumber) {
            return response()->json([
                'status' => 'error',
                'message' => 'Queue number not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $queueNumber
        ]);
    }

    public function update(Request $request, $id)
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $queueNumber = QueueNumber::where('client_identifier', $clientIdentifier)->find($id);

        if (!$queueNumber) {
            return response()->json([
                'status' => 'error',
                'message' => 'Queue number not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_name' => 'sometimes|nullable|string|max:255',
            'customer_contact' => 'sometimes|nullable|string|max:255',
            'status' => 'sometimes|in:waiting,called,serving,completed,cancelled',
            'priority' => 'sometimes|integer|min:0',
            'notes' => 'sometimes|nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $queueNumber->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Queue number updated successfully',
            'data' => $queueNumber->load('queueType')
        ]);
    }

    public function callNext(Request $request)
    {
        $clientIdentifier = $request->input('client_identifier');
        $queueTypeId = $request->input('queue_type_id');

        $query = QueueNumber::where('client_identifier', $clientIdentifier)
            ->where('status', 'waiting')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at');

        if ($queueTypeId) {
            $query->where('queue_type_id', $queueTypeId);
        }

        $nextNumber = $query->first();

        if (!$nextNumber) {
            return response()->json([
                'status' => 'error',
                'message' => 'No waiting queue numbers found'
            ], 404);
        }

        $nextNumber->markAsCalled();

        return response()->json([
            'status' => 'success',
            'message' => 'Next queue number called',
            'data' => $nextNumber->load('queueType')
        ]);
    }

    public function startServing(Request $request, $id)
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $queueNumber = QueueNumber::where('client_identifier', $clientIdentifier)->find($id);

        if (!$queueNumber) {
            return response()->json([
                'status' => 'error',
                'message' => 'Queue number not found'
            ], 404);
        }

        if ($queueNumber->status !== 'called') {
            return response()->json([
                'status' => 'error',
                'message' => 'Queue number must be called before serving'
            ], 400);
        }

        $queueNumber->markAsServing();

        return response()->json([
            'status' => 'success',
            'message' => 'Queue number marked as serving',
            'data' => $queueNumber->load('queueType')
        ]);
    }

    public function complete(Request $request, $id)
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $queueNumber = QueueNumber::where('client_identifier', $clientIdentifier)->find($id);

        if (!$queueNumber) {
            return response()->json([
                'status' => 'error',
                'message' => 'Queue number not found'
            ], 404);
        }

        $queueNumber->markAsCompleted();

        return response()->json([
            'status' => 'success',
            'message' => 'Queue number completed',
            'data' => $queueNumber->load('queueType')
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $queueNumber = QueueNumber::where('client_identifier', $clientIdentifier)->find($id);

        if (!$queueNumber) {
            return response()->json([
                'status' => 'error',
                'message' => 'Queue number not found'
            ], 404);
        }

        $queueNumber->markAsCancelled();

        return response()->json([
            'status' => 'success',
            'message' => 'Queue number cancelled',
            'data' => $queueNumber->load('queueType')
        ]);
    }

    public function getStatus(Request $request)
    {
        $clientIdentifier = $request->input('client_identifier');
        $queueTypeId = $request->input('queue_type_id');

        $baseQuery = QueueNumber::where('client_identifier', $clientIdentifier);
        
        if ($queueTypeId) {
            $baseQuery->where('queue_type_id', $queueTypeId);
        }

        $status = [
            'waiting' => (clone $baseQuery)->where('status', 'waiting')->count(),
            'called' => (clone $baseQuery)->where('status', 'called')->count(),
            'serving' => (clone $baseQuery)->where('status', 'serving')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count()
        ];

        $currentServing = (clone $baseQuery)
            ->where('status', 'serving')
            ->with('queueType')
            ->get();

        $calledNumbers = (clone $baseQuery)
            ->where('status', 'called')
            ->orderBy('called_at')
            ->with('queueType')
            ->get();

        $nextWaiting = (clone $baseQuery)
            ->where('status', 'waiting')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at')
            ->with('queueType')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'counts' => $status,
                'current_serving' => $currentServing,
                'called_numbers' => $calledNumbers,
                'next_waiting' => $nextWaiting
            ]
        ]);
    }
}
