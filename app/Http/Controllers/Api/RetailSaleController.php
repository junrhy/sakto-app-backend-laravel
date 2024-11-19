<?php

namespace App\Http\Controllers\Api;

use App\Models\RetailSale;
use App\Models\RetailItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RetailSaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $retailSales = RetailSale::where('client_identifier', $clientIdentifier)->get();

        return response()->json([
            'status' => 'success',
            'data' => $retailSales
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'total_amount' => 'required|numeric',
            'payment_method' => 'required|string',
            'client_identifier' => 'required',
        ]);
    
        $retailSale = RetailSale::create($request->all());
        
        // Update the inventory
        $items = $retailSale->items;
        foreach ($items as $item) {
            $retailItem = RetailItem::find($item['id']);
            $retailItem->update([
                'quantity' => $retailItem->quantity - $item['quantity']
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Retail sale created successfully',
            'data' => $retailSale
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $retailSale = RetailSale::find($id);
        $retailSale->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Retail sale deleted successfully'
        ], 200);
    }

    public function bulkDelete(Request $request)
    {
        RetailSale::destroy($request->input('ids'));

        return response()->json([
            'status' => 'success',
            'message' => 'Retail sales deleted successfully'
        ], 200);
    }

    public function getSalesOverview(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $todaySales = RetailSale::where('client_identifier', $clientIdentifier)->whereDate('created_at', now()->today())->sum('total_amount');
        $weeklySales = RetailSale::where('client_identifier', $clientIdentifier)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('total_amount');

        return response()->json([
            'todaySales' => $todaySales,
            'weeklySales' => $weeklySales
        ], 200);
    }
}
