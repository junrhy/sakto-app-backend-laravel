<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodDeliveryPayment;
use App\Models\FoodDeliveryOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FoodDeliveryPaymentController extends Controller
{
    /**
     * Display payment records for an order
     */
    public function index(Request $request, $orderId): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $order = FoodDeliveryOrder::where('id', $orderId)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $payments = FoodDeliveryPayment::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Process payment for an order
     */
    public function processPayment(Request $request, $orderId): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $order = FoodDeliveryOrder::where('id', $orderId)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => ['required', Rule::in(['online', 'cash_on_delivery'])],
            'payment_reference' => 'nullable|string|max:255',
            'payment_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Create payment record
        $payment = FoodDeliveryPayment::create([
            'order_id' => $order->id,
            'payment_method' => $validated['payment_method'],
            'amount' => $order->total_amount,
            'payment_reference' => $validated['payment_reference'] ?? FoodDeliveryOrder::generateOrderReference(),
            'payment_status' => $validated['payment_method'] === 'online' ? 'paid' : 'pending',
            'payment_data' => $validated['payment_data'] ?? null,
        ]);

        // Update order payment status
        if ($validated['payment_method'] === 'online') {
            $order->markAsPaid($payment->payment_reference);
        } else {
            $order->update(['payment_status' => 'pending']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => [
                'payment' => $payment,
                'order' => $order->fresh()
            ]
        ]);
    }

    /**
     * Mark payment as paid (for cash on delivery)
     */
    public function markAsPaid(Request $request, $orderId): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        $order = FoodDeliveryOrder::where('id', $orderId)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Order is already paid'
            ], 400);
        }

        $order->markAsPaid();

        return response()->json([
            'success' => true,
            'message' => 'Payment marked as paid',
            'data' => $order->fresh()->load('payments')
        ]);
    }
}
