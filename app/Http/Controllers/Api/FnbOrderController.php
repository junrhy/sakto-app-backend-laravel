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
    public function index()
    {
        $fnbOrders = fnbOrder::all();
        return response()->json($fnbOrders);
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

        $fnbOrder = fnbOrder::where('table_number', $tableNumber)->first();
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
    public function destroy(fnbOrder $fnbOrder)
    {
        $fnbOrder->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }
}
