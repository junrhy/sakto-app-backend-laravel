<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransportationBooking;
use App\Models\TransportationFleet;
use App\Services\TransportationPricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransportationBookingController extends Controller
{
    /**
     * Display a listing of bookings for a client
     */
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        $query = TransportationBooking::where('client_identifier', $clientIdentifier)
            ->with(['truck']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_email')) {
            $query->where('customer_email', $request->customer_email);
        }

        if ($request->has('booking_reference')) {
            $query->where('booking_reference', $request->booking_reference);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('booking_reference', 'like', "%{$search}%")
                  ->orWhere('pickup_location', 'like', "%{$search}%")
                  ->orWhere('delivery_location', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $bookings = $query->get();

        return response()->json($bookings);
    }

    /**
     * Store a newly created booking
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'truck_id' => 'required|integer|exists:transportation_fleets,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:255',
            'customer_company' => 'nullable|string|max:255',
            'pickup_location' => 'required|string|max:1000',
            'delivery_location' => 'required|string|max:1000',
            'pickup_date' => 'required|date|after_or_equal:today',
            'pickup_time' => 'required|date_format:H:i',
            'delivery_date' => 'required|date|after_or_equal:pickup_date',
            'delivery_time' => 'required|date_format:H:i',
            'cargo_description' => 'required|string|max:1000',
            'cargo_weight' => 'required|numeric|min:0.01',
            'cargo_unit' => ['required', Rule::in(['kg', 'tons', 'pieces', 'pallets', 'boxes', 'liters'])],
            'distance_km' => 'nullable|numeric|min:0',
            'route_type' => ['nullable', Rule::in(['local', 'provincial', 'intercity'])],
            'special_requirements' => 'nullable|string|max:1000',
            'requires_refrigeration' => 'nullable|boolean',
            'requires_special_equipment' => 'nullable|boolean',
            'requires_escort' => 'nullable|boolean',
            'is_urgent_delivery' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // Check if truck belongs to the client and is available
        $truck = TransportationFleet::where('id', $validated['truck_id'])
            ->where('client_identifier', $validated['client_identifier'])
            ->first();

        if (!$truck) {
            return response()->json([
                'success' => false,
                'message' => 'Truck not found or does not belong to this client'
            ], 404);
        }

        if ($truck->status !== 'Available') {
            return response()->json([
                'success' => false,
                'message' => 'Truck is not available for booking'
            ], 400);
        }

        // Check for booking conflicts
        $conflictingBooking = TransportationBooking::where('truck_id', $validated['truck_id'])
            ->where('status', '!=', 'Cancelled')
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('pickup_date', '<=', $validated['delivery_date'])
                      ->where('delivery_date', '>=', $validated['pickup_date']);
                });
            })
            ->first();

        if ($conflictingBooking) {
            return response()->json([
                'success' => false,
                'message' => 'Truck is already booked for the selected dates'
            ], 400);
        }

        // Generate booking reference
        $validated['booking_reference'] = TransportationBooking::generateBookingReference();

        // Calculate pricing using the pricing service
        $pricingService = new TransportationPricingService($validated['client_identifier']);
        $pricing = $pricingService->calculatePricing($validated);

        // Merge pricing data with validated data
        $validated = array_merge($validated, $pricing);

        $booking = TransportationBooking::create($validated);

        // Update truck status to "In Transit" if pickup date is today
        if ($validated['pickup_date'] === now()->format('Y-m-d')) {
            $truck->update(['status' => 'In Transit']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully',
            'data' => $booking->load('truck'),
            'booking_reference' => $booking->booking_reference
        ], 201);
    }

    /**
     * Display the specified booking
     */
    public function show(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }
        
        $booking = TransportationBooking::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->with(['truck'])
            ->first();
        
        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        return response()->json($booking);
    }

    /**
     * Update the specified booking
     */
    public function update(Request $request, $id): JsonResponse
    {
        $booking = TransportationBooking::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'status' => ['required', Rule::in(['Pending', 'Confirmed', 'In Progress', 'Completed', 'Cancelled'])],
            'notes' => 'nullable|string|max:1000',
            'estimated_cost' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        
        // Check if client_identifier matches the booking
        if ($booking->client_identifier !== $validated['client_identifier']) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $booking->update($validated);

        // Update truck status based on booking status
        if ($validated['status'] === 'Confirmed' && $booking->pickup_date === now()->format('Y-m-d')) {
            $booking->truck->update(['status' => 'In Transit']);
        } elseif ($validated['status'] === 'Completed') {
            $booking->truck->update(['status' => 'Available']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking updated successfully',
            'data' => $booking->load('truck')
        ]);
    }

    /**
     * Remove the specified booking
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }
        
        $booking = TransportationBooking::where('id', $id)
            ->where('client_identifier', $clientIdentifier)
            ->first();
        
        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        $booking->delete();
        return response()->json(['message' => 'Booking deleted successfully']);
    }

    /**
     * Get booking by reference number
     */
    public function getByReference(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'booking_reference' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $booking = TransportationBooking::where('booking_reference', $validated['booking_reference'])
            ->where('client_identifier', $validated['client_identifier'])
            ->with(['truck'])
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        return response()->json($booking);
    }

    /**
     * Get dashboard stats for bookings
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        $stats = [
            'total_bookings' => TransportationBooking::where('client_identifier', $clientIdentifier)->count(),
            'pending_bookings' => TransportationBooking::where('client_identifier', $clientIdentifier)->pending()->count(),
            'confirmed_bookings' => TransportationBooking::where('client_identifier', $clientIdentifier)->confirmed()->count(),
            'completed_bookings' => TransportationBooking::where('client_identifier', $clientIdentifier)->completed()->count(),
            'cancelled_bookings' => TransportationBooking::where('client_identifier', $clientIdentifier)->cancelled()->count(),
            'today_bookings' => TransportationBooking::where('client_identifier', $clientIdentifier)
                ->where('pickup_date', now()->format('Y-m-d'))
                ->count(),
            'upcoming_bookings' => TransportationBooking::where('client_identifier', $clientIdentifier)
                ->where('pickup_date', '>', now()->format('Y-m-d'))
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Process payment for a booking
     */
    public function processPayment(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'payment_method' => ['required', Rule::in(['cash', 'card', 'bank_transfer', 'digital_wallet'])],
            'payment_reference' => 'nullable|string|max:255',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $booking = TransportationBooking::where('id', $id)
            ->where('client_identifier', $validated['client_identifier'])
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        if ($booking->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Booking is already paid'
            ], 400);
        }

        // Generate payment reference if not provided
        $paymentReference = $validated['payment_reference'] ?? TransportationBooking::generatePaymentReference();

        // Mark as paid
        $booking->markAsPaid(
            $validated['payment_method'],
            $paymentReference,
            $validated['paid_amount'] ?? null,
            $validated['payment_notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => $booking->load('truck'),
            'payment_reference' => $paymentReference
        ]);
    }

    /**
     * Update payment status for a booking
     */
    public function updatePaymentStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string|max:255',
            'payment_status' => ['required', Rule::in(['pending', 'paid', 'failed', 'refunded'])],
            'payment_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $booking = TransportationBooking::where('id', $id)
            ->where('client_identifier', $validated['client_identifier'])
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        $booking->update([
            'payment_status' => $validated['payment_status'],
            'payment_notes' => $validated['payment_notes'] ?? $booking->payment_notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully',
            'data' => $booking->load('truck')
        ]);
    }

    /**
     * Get payment statistics for a client
     */
    public function paymentStats(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        $stats = [
            'total_paid' => TransportationBooking::where('client_identifier', $clientIdentifier)
                ->where('payment_status', 'paid')
                ->sum('paid_amount'),
            'total_pending' => TransportationBooking::where('client_identifier', $clientIdentifier)
                ->where('payment_status', 'pending')
                ->sum('estimated_cost'),
            'total_failed' => TransportationBooking::where('client_identifier', $clientIdentifier)
                ->where('payment_status', 'failed')
                ->sum('estimated_cost'),
            'paid_bookings_count' => TransportationBooking::where('client_identifier', $clientIdentifier)
                ->where('payment_status', 'paid')
                ->count(),
            'pending_payments_count' => TransportationBooking::where('client_identifier', $clientIdentifier)
                ->where('payment_status', 'pending')
                ->count(),
            'failed_payments_count' => TransportationBooking::where('client_identifier', $clientIdentifier)
                ->where('payment_status', 'failed')
                ->count(),
        ];

        return response()->json($stats);
    }
}
