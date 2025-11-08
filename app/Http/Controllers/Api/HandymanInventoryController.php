<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HandymanInventoryItem;
use App\Models\HandymanInventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HandymanInventoryController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'type' => 'nullable|in:tool,consumable',
            'category' => 'nullable|string',
            'search' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = HandymanInventoryItem::where('client_identifier', $request->client_identifier);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $items = $query->withSum(['transactions as total_checked_out' => function ($q) {
            $q->where('transaction_type', 'check_out');
        }], 'quantity')
            ->withSum(['transactions as total_consumed' => function ($q) {
                $q->where('transaction_type', 'consume');
            }], 'quantity')
            ->orderBy('name')
            ->paginate(20);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request)
    {
        $validator = $this->itemValidator($request);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item = HandymanInventoryItem::create($validator->validated());

        return response()->json([
            'message' => 'Inventory item created successfully',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = $this->itemValidator($request, false);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item = HandymanInventoryItem::where('client_identifier', $request->client_identifier)
            ->findOrFail($id);

        $item->update($validator->validated());

        return response()->json([
            'message' => 'Inventory item updated successfully',
            'data' => $item->fresh(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $clientIdentifier = $request->query('client_identifier') ?? $request->input('client_identifier');

        $item = HandymanInventoryItem::where('client_identifier', $clientIdentifier)
            ->findOrFail($id);

        if ($item->transactions()->exists()) {
            return response()->json([
                'error' => 'Cannot delete inventory item with existing transactions',
            ], 409);
        }

        $item->delete();

        return response()->json(['message' => 'Inventory item deleted successfully']);
    }

    public function transactions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'inventory_item_id' => 'nullable|integer|exists:handyman_inventory_items,id',
            'transaction_type' => 'nullable|string|in:check_out,check_in,consume,adjust',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = HandymanInventoryTransaction::with(['item', 'technician', 'workOrder'])
            ->where('client_identifier', $request->client_identifier)
            ->orderByDesc('transaction_at')
            ->orderByDesc('created_at');

        if ($request->filled('inventory_item_id')) {
            $query->where('inventory_item_id', $request->inventory_item_id);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        return response()->json(['data' => $query->paginate(30)]);
    }

    public function recordTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'inventory_item_id' => 'required|integer|exists:handyman_inventory_items,id',
            'transaction_type' => 'required|string|in:check_out,check_in,consume,adjust',
            'quantity' => 'required|integer',
            'technician_id' => 'nullable|integer|exists:handyman_technicians,id',
            'work_order_id' => 'nullable|integer|exists:handyman_work_orders,id',
            'details' => 'nullable|array',
            'transaction_at' => 'nullable|date',
            'recorded_by' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $transaction = DB::transaction(function () use ($data) {
            $item = HandymanInventoryItem::lockForUpdate()->findOrFail($data['inventory_item_id']);

            $transaction = HandymanInventoryTransaction::create(array_merge($data, [
                'transaction_at' => $data['transaction_at'] ?? now(),
            ]));

            if ($data['transaction_type'] === 'check_out') {
                $item->quantity_available = max(0, $item->quantity_available - $data['quantity']);
            } elseif ($data['transaction_type'] === 'check_in') {
                $item->quantity_available += $data['quantity'];
            } elseif ($data['transaction_type'] === 'consume') {
                $item->quantity_available = max(0, $item->quantity_available - $data['quantity']);
                $item->quantity_on_hand = max(0, $item->quantity_on_hand - $data['quantity']);
            } elseif ($data['transaction_type'] === 'adjust') {
                $item->quantity_available = max(0, $item->quantity_available + $data['quantity']);
                $item->quantity_on_hand = max(0, $item->quantity_on_hand + $data['quantity']);
            }

            $item->save();

            return $transaction->load(['item', 'technician', 'workOrder']);
        });

        return response()->json([
            'message' => 'Inventory transaction recorded successfully',
            'data' => $transaction,
        ], 201);
    }

    public function lowStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $items = HandymanInventoryItem::where('client_identifier', $request->client_identifier)
            ->whereColumn('quantity_available', '<=', 'reorder_level')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $items]);
    }

    protected function itemValidator(Request $request, bool $isCreate = true)
    {
        $rules = [
            'client_identifier' => 'required|string',
            'sku' => 'nullable|string|max:255',
            'name' => $isCreate ? 'required|string|max:255' : 'nullable|string|max:255',
            'type' => 'nullable|in:tool,consumable',
            'category' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'quantity_on_hand' => 'nullable|integer|min:0',
            'quantity_available' => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'requires_check_in' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];

        return Validator::make($request->all(), $rules);
    }
}

