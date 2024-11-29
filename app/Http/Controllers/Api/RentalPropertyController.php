<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RentalProperty;
use App\Models\RentalPropertyPayment;
use Illuminate\Http\Request;

class RentalPropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $properties = RentalProperty::where('client_identifier', $clientIdentifier)->get();
        $payments = RentalPropertyPayment::where('client_identifier', $clientIdentifier)->get();

        return response()->json([
            'success' => true,
            'message' => 'Rental properties fetched successfully',
            'data' => [
                'properties' => $properties,
                'payments' => $payments
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'address' => 'required|string',
            'type' => 'required|string',
            'bedrooms' => 'required|integer',
            'bathrooms' => 'required|integer',
            'rent' => 'required|numeric',
            'status' => 'required|string',
            'tenant_name' => 'nullable|string',
            'lease_start' => 'nullable|date',
            'lease_end' => 'nullable|date',
            'last_payment_received' => 'nullable|date',
        ]);

        $data = $request->all();
        $data['client_identifier'] = $request->client_identifier;

        RentalProperty::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Rental property created successfully',
            'data' => $data
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'address' => 'required|string',
            'type' => 'required|string',
            'bedrooms' => 'required|integer',
            'bathrooms' => 'required|integer',
            'rent' => 'required|numeric',
            'status' => 'required|string',
            'tenant_name' => 'nullable|string',
            'lease_start' => 'nullable|date',
            'lease_end' => 'nullable|date',
            'last_payment_received' => 'nullable|date',
        ]);

        $data = $request->all();
        $data['client_identifier'] = $request->client_identifier;

        RentalProperty::find($id)->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Rental property updated successfully',
            'data' => $data
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        RentalProperty::find($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rental property deleted successfully'
        ]);
    }

    public function bulkDestroy(Request $request)
    {
        RentalProperty::whereIn('id', $request->ids)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Rental properties deleted successfully'
        ]);
    }
    
    public function recordPayment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'payment_date' => 'required|date',
        ]);

        $data = $request->all();
        $data['client_identifier'] = $request->client_identifier;

        $rentalPropertyPayment = RentalPropertyPayment::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'data' => $rentalPropertyPayment
        ]);
    }

    public function getPaymentHistory($id)
    {
        $payments = RentalPropertyPayment::where('rental_property_id', $id)->get();
        return response()->json([
            'success' => true,
            'message' => 'Payment history fetched successfully',
            'data' => $payments
        ]);
    }
}
