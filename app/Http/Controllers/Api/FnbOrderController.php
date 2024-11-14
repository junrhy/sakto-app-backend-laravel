<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbTable;
use App\Models\FnbMenuItem;
use App\Models\FnbOrder;
use App\Models\FnbSale;
use App\Models\FnbKitchenOrder;
use Illuminate\Http\Request;

class FnbOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($clientIdentifier, $tableNumber)
    {
        $fnbTable = FnbTable::where('name', $tableNumber)->first();
        $fnbOrders = FnbOrder::where('client_identifier', $clientIdentifier)->where('table_number', $fnbTable->name)->get();

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



        return response()->json(['order' => ['items' => $items]]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function addItemToOrder(Request $request)
    {

        $fnbTable = FnbTable::where('name', $request->table_number)->first();
        if ($fnbTable->status === 'available') {
            $fnbTable->update(['status' => 'occupied']);
        }
        
        $fnbMenuItem = FnbMenuItem::where('id', $request->item_id)->first();

        $fnbOrder = FnbOrder::where('table_number', $request->table_number)->where('item', $fnbMenuItem->name)->first();
        if ($fnbOrder) {
            $fnbOrder->update([
                'quantity' => $fnbOrder->quantity + $request->quantity,
                'total' => ($fnbOrder->quantity + $request->quantity) * $request->price
            ]);
            return response()->json($fnbOrder, 200);
        } else {
            $fnbOrder = FnbOrder::create([
                'table_number' => $request->table_number,
                'item' => $fnbMenuItem->name,
                'quantity' => $request->quantity,
                'price' => $request->price,
                'total' => $request->total,
                'client_identifier' => $request->client_identifier
            ]);
            $fnbOrder->id = $fnbOrder->id;
            return response()->json($fnbOrder, 201);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $tableNumber, int $id)
    {
        $fnbOrder = FnbOrder::where('id', $id)->first();
        $fnbOrder->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }

    public function completeOrder(Request $request)
    {
        $validated = $request->validate([
            'table_number' => 'required',
            'items' => 'required',
            'subtotal' => 'required',
            'discount' => 'required',
            'discount_type' => 'required',
            'total' => 'required',
            'client_identifier' => 'required'
        ]);

        FnbOrder::where('table_number', $request->table_number)->update(['status' => 'completed']);
        FnbTable::where('name', $request->table_number)->update(['status' => 'available']);

        $fnbSale = FnbSale::create([
            'table_number' => $request->table_number,
            'items' => $request->items,
            'subtotal' => $request->subtotal,
            'discount' => $request->discount,
            'discount_type' => $request->discount_type,
            'total' => $request->total,
            'client_identifier' => $request->client_identifier
        ]);

        return response()->json($fnbSale);
    }

    public function storeKitchenOrder(Request $request)
    {
        $validated = $request->validate([
            'table_number' => 'required',
            'items' => 'required',
            'client_identifier' => 'required'
        ]);

        FnbKitchenOrder::create([
            'table_number' => $request->table_number,
            'items' => json_encode($request->items),
            'status' => 'pending',
            'client_identifier' => $request->client_identifier
        ]);

        return response()->json(['message' => 'Kitchen order created successfully']);
    }
}
