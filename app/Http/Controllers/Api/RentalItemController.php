<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RentalItem;
use Illuminate\Http\Request;

class RentalItemController extends Controller
{
    public function index(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $items = RentalItem::where('client_identifier', $clientIdentifier)->get();

        return response()->json([
            'success' => true,
            'message' => 'Rental items fetched successfully',
            'data' => $items
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'daily_rate' => 'required|numeric',
            'quantity' => 'required|integer',
            'status' => 'required|string'
        ]);

        $data = $request->all();
        $data['client_identifier'] = $request->client_identifier;

        try {
            $item = RentalItem::create($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rental item created successfully',
            'data' => $item
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'daily_rate' => 'required|numeric',
            'quantity' => 'required|integer',
            'status' => 'required|string'
        ]);

        $item = RentalItem::findOrFail($id);
        $item->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Rental item updated successfully',
            'data' => $item
        ]);
    }

    public function destroy($id)
    {
        RentalItem::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rental item deleted successfully'
        ]);
    }

    public function bulkDestroy(Request $request)
    {
        RentalItem::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rental items deleted successfully'
        ]);
    }

    public function recordPayment(Request $request, $id)
    {
        return $request->all();
    }

    public function getPaymentHistory($id)
    {
        return $id;
    }
}
