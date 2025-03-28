<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FoodDeliveryOrder;

class FoodDeliveryOrderController extends Controller
{
    public function index()
    {
        $orders = FoodDeliveryOrder::all();
        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $request->validate([
            'app_name' => 'required',
            'customer_name' => 'required',
            'customer_phone' => 'required',
            'customer_address' => 'required',
            'customer_email' => 'nullable|email',
            'items' => 'required|array',
            'items.*.name' => 'required',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.delivery_fee' => 'required|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
            'items.*.restaurant_name' => 'required',
            'items.*.restaurant_address' => 'required',
            'items.*.restaurant_phone' => 'required',
            'items.*.restaurant_email' => 'nullable|email',
            'total_amount' => 'required|numeric|min:0',
            'delivery_fee' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'special_instructions' => 'nullable',
            'order_payment_method' => 'required',
            'order_payment_status' => 'required|in:pending,paid,failed',
            'order_payment_reference' => 'nullable',
        ]);

        $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(FoodDeliveryOrder::count() + 1, 4, '0', STR_PAD_LEFT);
        $request['order_number'] = $order_number;

        $order = FoodDeliveryOrder::create($request->all());
        return response()->json($order, 201);
    }

    public function show($id)
    {
        $order = FoodDeliveryOrder::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        return response()->json($order);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'order_status' => 'required|in:pending,confirmed,preparing,ready,delivered,cancelled',
            'order_payment_status' => 'required|in:pending,paid,failed',
            'order_payment_reference' => 'nullable',
        ]);

        $order = FoodDeliveryOrder::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        $order->update($request->all());
        return response()->json($order);
    }

    public function destroy($id)
    {
        $order = FoodDeliveryOrder::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }
}