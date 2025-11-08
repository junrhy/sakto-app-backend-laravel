<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HandymanTask;
use App\Models\HandymanTaskAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class HandymanTaskController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'date' => 'nullable|date',
            'status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = HandymanTask::with(['assignments.technician'])
            ->where('client_identifier', $request->client_identifier)
            ->orderBy('scheduled_start_at');

        if ($request->filled('date')) {
            $query->whereDate('scheduled_start_at', $request->date);
        }

        if ($request->filled('status')) {
            $query->whereIn('status', (array) $request->status);
        }

        $tasks = $query->get();

        return response()->json(['data' => $tasks]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:scheduled,in_progress,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'scheduled_start_at' => 'nullable|date',
            'scheduled_end_at' => 'nullable|date|after_or_equal:scheduled_start_at',
            'location' => 'nullable|string|max:255',
            'coordinates' => 'nullable|array',
            'tags' => 'nullable|array',
            'required_resources' => 'nullable|array',
            'assignments' => 'nullable|array',
            'assignments.*.technician_id' => 'required_with:assignments|integer|exists:handyman_technicians,id',
            'assignments.*.assigned_start_at' => 'nullable|date',
            'assignments.*.assigned_end_at' => 'nullable|date|after_or_equal:assignments.*.assigned_start_at',
            'assignments.*.is_primary' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $task = DB::transaction(function () use ($data) {
            $taskData = collect($data)->except('assignments')->toArray();
            $taskData['reference_number'] = $this->generateReferenceNumber();

            $task = HandymanTask::create($taskData);

            if (!empty($data['assignments'])) {
                $this->syncAssignments($task, $data['assignments']);
            }

            return $task->load('assignments.technician');
        });

        return response()->json([
            'message' => 'Task created successfully',
            'data' => $task,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:scheduled,in_progress,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'scheduled_start_at' => 'nullable|date',
            'scheduled_end_at' => 'nullable|date|after_or_equal:scheduled_start_at',
            'location' => 'nullable|string|max:255',
            'coordinates' => 'nullable|array',
            'tags' => 'nullable|array',
            'required_resources' => 'nullable|array',
            'assignments' => 'nullable|array',
            'assignments.*.technician_id' => 'required_with:assignments|integer|exists:handyman_technicians,id',
            'assignments.*.assigned_start_at' => 'nullable|date',
            'assignments.*.assigned_end_at' => 'nullable|date|after_or_equal:assignments.*.assigned_start_at',
            'assignments.*.is_primary' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task = HandymanTask::where('client_identifier', $request->client_identifier)
            ->findOrFail($id);

        $data = $validator->validated();

        $task = DB::transaction(function () use ($task, $data) {
            $task->update(collect($data)->except('assignments')->toArray());

            if (array_key_exists('assignments', $data)) {
                $this->syncAssignments($task, $data['assignments'] ?? []);
            }

            return $task->load('assignments.technician');
        });

        return response()->json([
            'message' => 'Task updated successfully',
            'data' => $task,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $clientIdentifier = $request->query('client_identifier') ?? $request->input('client_identifier');

        $task = HandymanTask::where('client_identifier', $clientIdentifier)
            ->findOrFail($id);

        if ($task->workOrders()->exists()) {
            return response()->json([
                'error' => 'Cannot delete task linked to work orders',
            ], 409);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }

    public function overview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'start' => 'nullable|date',
            'end' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = HandymanTask::where('client_identifier', $request->client_identifier);

        if ($request->filled('start')) {
            $query->whereDate('scheduled_start_at', '>=', $request->start);
        }

        if ($request->filled('end')) {
            $query->whereDate('scheduled_end_at', '<=', $request->end);
        }

        $data = $query->select([
            'status',
            DB::raw('count(*) as total'),
        ])->groupBy('status')->get();

        return response()->json(['data' => $data]);
    }

    protected function syncAssignments(HandymanTask $task, array $assignments): void
    {
        $task->assignments()->delete();

        foreach ($assignments as $assignment) {
            $task->assignments()->create([
                'client_identifier' => $task->client_identifier,
                'technician_id' => $assignment['technician_id'],
                'assigned_start_at' => $assignment['assigned_start_at'] ?? $task->scheduled_start_at,
                'assigned_end_at' => $assignment['assigned_end_at'] ?? $task->scheduled_end_at,
                'is_primary' => $assignment['is_primary'] ?? false,
                'conflict_status' => $this->determineConflictStatus(
                    $task->client_identifier,
                    $assignment['technician_id'],
                    $assignment['assigned_start_at'] ?? $task->scheduled_start_at,
                    $assignment['assigned_end_at'] ?? $task->scheduled_end_at
                ),
            ]);
        }
    }

    protected function determineConflictStatus(string $clientIdentifier, int $technicianId, ?string $start, ?string $end): string
    {
        if (!$start || !$end) {
            return 'none';
        }

        $overlap = HandymanTaskAssignment::where('client_identifier', $clientIdentifier)
            ->where('technician_id', $technicianId)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('assigned_start_at', [$start, $end])
                    ->orWhereBetween('assigned_end_at', [$start, $end])
                    ->orWhere(function ($subQuery) use ($start, $end) {
                        $subQuery->where('assigned_start_at', '<=', $start)
                            ->where('assigned_end_at', '>=', $end);
                    });
            })
            ->exists();

        return $overlap ? 'overlap' : 'none';
    }

    protected function generateReferenceNumber(): string
    {
        return 'HDT-' . Str::upper(Str::random(6));
    }
}

