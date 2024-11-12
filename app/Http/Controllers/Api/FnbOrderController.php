<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbTable;
use App\Models\FnbMenuItem;
use App\Models\fnbOrder;
use Illuminate\Http\Request;

class FnbOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($clientIdentifier, $tableNumber)
    {
        $fnbTable = FnbTable::where('name', $tableNumber)->first();
        $fnbOrders = fnbOrder::where('client_identifier', $clientIdentifier)->where('table_number', $fnbTable->name)->get();

        $items = $fnbOrders->map(function ($order) {
            return [
                'id' => $order->id,
                'table_number' => $order->table_number,
                'item' => $order->item,
                'quantity' => $order->quantity,
                'price' => $order->price,
                'total' => $order->total
            ];
        });



        return response()->json(['orders' => ['items' => $items]]);
    }

    public function getOrCreateOrder(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'table_number' => 'required|string',
                'client_identifier' => 'required|string'
            ]);

            // Find the table
            $fnbTable = FnbTable::where('name', $validated['table_number'])->first();
            if (!$fnbTable) {
                return response()->json(['error' => 'Table not found'], 404);
            }

            // Find existing order for this table
            $existingOrder = fnbOrder::where([
                'table_number' => $validated['table_number'],
                'client_identifier' => $validated['client_identifier'],
                'status' => 'active' // Assuming you have a status column
            ])->first();

            if ($existingOrder) {
                // Return existing order
                return response()->json([
                    'order' => [
                        'id' => $existingOrder->id,
                        'table_number' => $existingOrder->table_number,
                        'status' => $existingOrder->status,
                        'created_at' => $existingOrder->created_at
                    ]
                ]);
            }

            // Create new order if none exists
            $newOrder = fnbOrder::create([
                'table_number' => $validated['table_number'],
                'client_identifier' => $validated['client_identifier'],
                'status' => 'active'
            ]);

            // Update table status if it's available
            if ($fnbTable->status === 'available') {
                $fnbTable->update(['status' => 'occupied']);
            }

            return response()->json([
                'order' => [
                    'id' => $newOrder->id,
                    'table_number' => $newOrder->table_number,
                    'status' => $newOrder->status,
                    'created_at' => $newOrder->created_at
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get or create order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $tableNumber)
    {
        $fnbTable = FnbTable::where('name', $tableNumber)->first();
        if ($fnbTable->status === 'available') {
            $fnbTable->update(['status' => 'occupied']);
        }
        
        $fnbMenuItem = FnbMenuItem::where('id', $request->items[0]['id'])->first();

        $fnbOrder = fnbOrder::where('table_number', $tableNumber)->where('item', $fnbMenuItem->name)->first();
        if ($fnbOrder) {
            $fnbOrder->update([
                'quantity' => $request->items[0]['quantity'],
                'total' => $request->items[0]['total']
            ]);
        } else {
            $fnbOrder = fnbOrder::create([
                'table_number' => $tableNumber,
                'item' => $fnbMenuItem->name,
                'quantity' => $request->items[0]['quantity'],
                'price' => $request->items[0]['price'],
                'total' => $request->items[0]['total'],
                'client_identifier' => $request->client_identifier
            ]);
            return response()->json($fnbOrder, 201);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $tableNumber, int $id)
    {
        // $fnbMenuItem = FnbMenuItem::where('id', $id)->first();
        // $fnbOrder = fnbOrder::where('table_number', $tableNumber)->where('item', $fnbMenuItem->name)->first();
        $fnbOrder = fnbOrder::where('id', $id)->first();
        $fnbOrder->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }
}
