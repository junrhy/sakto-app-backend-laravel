<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
    public function store(Request $request)
    {
        $fnbOrder = fnbOrder::create($request->all());
        return response()->json($fnbOrder, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, fnbOrder $fnbOrder)
    {
        $fnbOrder->update($request->all());
        return response()->json($fnbOrder);
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
