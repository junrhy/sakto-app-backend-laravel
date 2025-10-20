<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbSale;
use Illuminate\Http\Request;

class FnbSaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $sales = FnbSale::where('client_identifier', $clientIdentifier)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'sales' => $sales
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, fnbSale $fnbSale)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(fnbSale $fnbSale)
    {
        //
    }
}
