<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HandymanInventoryTransaction;
use App\Models\HandymanWorkOrder;
use App\Models\HandymanWorkOrderAttachment;
use App\Models\HandymanWorkOrderTimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class HandymanWorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'status' => 'nullable|array',
            'status.*' => 'string',
            'search' => 'nullable|string',
            'technician_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = HandymanWorkOrder::with(['task', 'technician', 'timeLogs', 'attachments'])
            ->where('client_identifier', $request->client_identifier)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->whereIn('status', $request->status);
        }

        if ($request->filled('technician_id')) {
            $query->where('technician_id', $request->technician_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_contact', 'like', "%{$search}%");
            });
        }

        return response()->json(['data' => $query->paginate(20)]);
    }

    public function store(Request $request)
    {
        $validator = $this->workOrderValidator($request);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $workOrder = DB::transaction(function () use ($data, $request) {
            $workOrderData = collect($data)->except(['time_logs', 'attachments', 'inventory_transactions'])->toArray();
            $workOrderData['reference_number'] = $this->generateReferenceNumber();

            $workOrder = HandymanWorkOrder::create($workOrderData);

            if (!empty($data['time_logs'])) {
                $this->syncTimeLogs($workOrder, $data['time_logs']);
            }

            if (!empty($data['attachments'])) {
                $this->syncAttachments($workOrder, $data['attachments']);
            }

            if (!empty($data['inventory_transactions'])) {
                $this->syncInventoryTransactions($workOrder, $data['inventory_transactions']);
            }

            return $workOrder->load(['task', 'technician', 'timeLogs', 'attachments']);
        });

        return response()->json([
            'message' => 'Work order created successfully',
            'data' => $workOrder,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $clientIdentifier = $request->query('client_identifier');

        $workOrder = HandymanWorkOrder::where('client_identifier', $clientIdentifier)
            ->with(['task', 'technician', 'timeLogs.technician', 'attachments'])
            ->findOrFail($id);

        return response()->json(['data' => $workOrder]);
    }

    public function update(Request $request, $id)
    {
        $validator = $this->workOrderValidator($request, false);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $workOrder = HandymanWorkOrder::where('client_identifier', $request->client_identifier)
            ->findOrFail($id);

        $data = $validator->validated();

        $workOrder = DB::transaction(function () use ($workOrder, $data) {
            $workOrder->update(collect($data)->except(['time_logs', 'attachments', 'inventory_transactions'])->toArray());

            if (array_key_exists('time_logs', $data)) {
                $this->syncTimeLogs($workOrder, $data['time_logs'] ?? []);
            }

            if (array_key_exists('attachments', $data)) {
                $this->syncAttachments($workOrder, $data['attachments'] ?? []);
            }

            if (array_key_exists('inventory_transactions', $data)) {
                $this->syncInventoryTransactions($workOrder, $data['inventory_transactions'] ?? []);
            }

            return $workOrder->load(['task', 'technician', 'timeLogs', 'attachments']);
        });

        return response()->json([
            'message' => 'Work order updated successfully',
            'data' => $workOrder,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $clientIdentifier = $request->query('client_identifier') ?? $request->input('client_identifier');

        $workOrder = HandymanWorkOrder::where('client_identifier', $clientIdentifier)
            ->findOrFail($id);

        $workOrder->delete();

        return response()->json(['message' => 'Work order deleted successfully']);
    }

    public function storeAttachment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'file' => 'required|file|max:10240',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $workOrder = HandymanWorkOrder::where('client_identifier', $request->client_identifier)
            ->findOrFail($id);

        $path = $request->file('file')->store('handyman/attachments', 'public');

        $attachment = $workOrder->attachments()->create([
            'client_identifier' => $request->client_identifier,
            'file_path' => Storage::disk('public')->url($path),
            'file_type' => $request->file('file')->getClientMimeType(),
            'uploaded_by' => $request->user()?->id,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Attachment uploaded successfully',
            'data' => $attachment,
        ], 201);
    }

    protected function workOrderValidator(Request $request, bool $isCreate = true)
    {
        $rules = [
            'client_identifier' => 'required|string',
            'task_id' => 'nullable|integer|exists:handyman_tasks,id',
            'technician_id' => 'nullable|integer|exists:handyman_technicians,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_contact' => 'nullable|string|max:255',
            'customer_address' => 'nullable|string',
            'scope_of_work' => 'nullable|string',
            'materials' => 'nullable|array',
            'checklist' => 'nullable|array',
            'approval' => 'nullable|array',
            'status' => 'nullable|in:draft,assigned,in_progress,awaiting_approval,completed,cancelled',
            'scheduled_at' => 'nullable|date',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date|after_or_equal:started_at',
            'notes' => 'nullable|string',
            'time_logs' => 'nullable|array',
            'time_logs.*.id' => 'nullable|integer|exists:handyman_work_order_time_logs,id',
            'time_logs.*.technician_id' => 'nullable|integer|exists:handyman_technicians,id',
            'time_logs.*.started_at' => 'required|date',
            'time_logs.*.ended_at' => 'nullable|date|after_or_equal:time_logs.*.started_at',
            'time_logs.*.duration_minutes' => 'nullable|integer|min:0',
            'time_logs.*.notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*.id' => 'nullable|integer|exists:handyman_work_order_attachments,id',
            'attachments.*.file_path' => 'required|string',
            'attachments.*.file_type' => 'nullable|string|max:255',
            'attachments.*.thumbnail_path' => 'nullable|string',
            'attachments.*.uploaded_by' => 'nullable|integer',
            'attachments.*.description' => 'nullable|string',
            'inventory_transactions' => 'nullable|array',
            'inventory_transactions.*.id' => 'nullable|integer|exists:handyman_inventory_transactions,id',
            'inventory_transactions.*.inventory_item_id' => 'required|integer|exists:handyman_inventory_items,id',
            'inventory_transactions.*.technician_id' => 'nullable|integer|exists:handyman_technicians,id',
            'inventory_transactions.*.transaction_type' => 'required|string|in:check_out,check_in,consume,adjust',
            'inventory_transactions.*.quantity' => 'required|integer',
            'inventory_transactions.*.details' => 'nullable|array',
            'inventory_transactions.*.transaction_at' => 'nullable|date',
        ];

        if ($isCreate) {
            $rules['status'] = 'required|in:draft,assigned,in_progress,awaiting_approval,completed,cancelled';
        }

        return Validator::make($request->all(), $rules);
    }

    protected function syncTimeLogs(HandymanWorkOrder $workOrder, array $timeLogs): void
    {
        $existingIds = collect($timeLogs)->pluck('id')->filter()->all();

        $workOrder->timeLogs()
            ->whereNotIn('id', $existingIds)
            ->delete();

        foreach ($timeLogs as $timeLog) {
            $payload = [
                'client_identifier' => $workOrder->client_identifier,
                'technician_id' => $timeLog['technician_id'] ?? null,
                'started_at' => $timeLog['started_at'],
                'ended_at' => $timeLog['ended_at'] ?? null,
                'duration_minutes' => $timeLog['duration_minutes'] ?? null,
                'notes' => $timeLog['notes'] ?? null,
            ];

            if (!empty($timeLog['id'])) {
                HandymanWorkOrderTimeLog::where('id', $timeLog['id'])
                    ->where('work_order_id', $workOrder->id)
                    ->update($payload);
            } else {
                $workOrder->timeLogs()->create($payload);
            }
        }
    }

    protected function syncAttachments(HandymanWorkOrder $workOrder, array $attachments): void
    {
        $existingIds = collect($attachments)->pluck('id')->filter()->all();

        $workOrder->attachments()
            ->whereNotIn('id', $existingIds)
            ->delete();

        foreach ($attachments as $attachment) {
            $payload = [
                'client_identifier' => $workOrder->client_identifier,
                'file_path' => $attachment['file_path'],
                'file_type' => $attachment['file_type'] ?? null,
                'thumbnail_path' => $attachment['thumbnail_path'] ?? null,
                'uploaded_by' => $attachment['uploaded_by'] ?? null,
                'description' => $attachment['description'] ?? null,
            ];

            if (!empty($attachment['id'])) {
                HandymanWorkOrderAttachment::where('id', $attachment['id'])
                    ->where('work_order_id', $workOrder->id)
                    ->update($payload);
            } else {
                $workOrder->attachments()->create($payload);
            }
        }
    }

    protected function syncInventoryTransactions(HandymanWorkOrder $workOrder, array $transactions): void
    {
        $existingIds = collect($transactions)->pluck('id')->filter()->all();

        $workOrder->inventoryTransactions()
            ->whereNotIn('id', $existingIds)
            ->delete();

        foreach ($transactions as $transaction) {
            $payload = [
                'client_identifier' => $workOrder->client_identifier,
                'inventory_item_id' => $transaction['inventory_item_id'],
                'technician_id' => $transaction['technician_id'] ?? null,
                'transaction_type' => $transaction['transaction_type'],
                'quantity' => $transaction['quantity'],
                'details' => $transaction['details'] ?? null,
                'transaction_at' => $transaction['transaction_at'] ?? now(),
                'recorded_by' => $transaction['recorded_by'] ?? null,
            ];

            if (!empty($transaction['id'])) {
                HandymanInventoryTransaction::where('id', $transaction['id'])
                    ->where('work_order_id', $workOrder->id)
                    ->update($payload);
            } else {
                $workOrder->inventoryTransactions()->create($payload);
            }
        }
    }

    protected function generateReferenceNumber(): string
    {
        return 'HDW-' . Str::upper(Str::random(6));
    }
}

