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
    public function index()
    {
        $retailSales = RetailSale::all();

        return response()->json([
            'status' => 'success',
            'data' => $retailSales
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
     * Display the specified resource.
     */
    public function show(RetailSale $retailSale)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RetailSale $retailSale)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RetailSale $retailSale)
    {
        //
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
}