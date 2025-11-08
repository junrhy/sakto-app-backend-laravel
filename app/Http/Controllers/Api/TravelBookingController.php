<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TravelBooking;
use App\Models\TravelPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TravelBookingController extends Controller
{
    /**
     * Display a listing of the travel bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_identifier' => ['required', 'string'],
            'status' => ['nullable', 'string'],
            'payment_status' => ['nullable', 'string'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'search' => ['nullable', 'string'],
            'travel_package_id' => ['nullable', 'integer', 'exists:travel_packages,id'],
        ]);

        $query = TravelBooking::query()
            ->with(['package:id,title,slug'])
            ->where('client_identifier', $validated['client_identifier'])
            ->latest();

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['payment_status'])) {
            $query->where('payment_status', $validated['payment_status']);
        }

        if (!empty($validated['travel_package_id'])) {
            $query->where('travel_package_id', $validated['travel_package_id']);
        }

        if (!empty($validated['from_date'])) {
            $query->whereDate('travel_date', '>=', $validated['from_date']);
        }

        if (!empty($validated['to_date'])) {
            $query->whereDate('travel_date', '<=', $validated['to_date']);
        }

        if (!empty($validated['search'])) {
            $query->where(function ($subQuery) use ($validated) {
                $subQuery
                    ->where('customer_name', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('customer_email', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('booking_reference', 'like', '%' . $validated['search'] . '%');
            });
        }

        $bookings = $query->paginate(
            $request->integer('per_page', 15)
        );

        return response()->json($bookings);
    }

    /**
     * Store a newly created booking.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_identifier' => ['required', 'string'],
            'travel_package_id' => ['required', 'integer', 'exists:travel_packages,id'],
            'booking_reference' => ['nullable', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_contact_number' => ['nullable', 'string', 'max:50'],
            'travel_date' => ['required', 'date', 'after_or_equal:today'],
            'travelers_count' => ['required', 'integer', 'min:1'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:100'],
            'payment_status' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $package = TravelPackage::where('client_identifier', $validated['client_identifier'])
            ->findOrFail($validated['travel_package_id']);

        $bookingReference = $validated['booking_reference'] ?? $this->generateBookingReference($package);

        $booking = TravelBooking::create(array_merge($validated, [
            'booking_reference' => $bookingReference,
            'status' => $validated['status'] ?? 'pending',
            'payment_status' => $validated['payment_status'] ?? 'unpaid',
        ]));

        return response()->json([
            'status' => 'success',
            'data' => $booking->load('package:id,title'),
        ], 201);
    }

    /**
     * Display the specified booking.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'client_identifier' => ['required', 'string'],
        ]);

        $booking = TravelBooking::where('client_identifier', $validated['client_identifier'])
            ->with('package')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $booking,
        ]);
    }

    /**
     * Update the specified booking.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'client_identifier' => ['required', 'string'],
            'travel_package_id' => ['sometimes', 'integer', 'exists:travel_packages,id'],
            'booking_reference' => ['nullable', 'string', 'max:100'],
            'customer_name' => ['sometimes', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_contact_number' => ['nullable', 'string', 'max:50'],
            'travel_date' => ['nullable', 'date'],
            'travelers_count' => ['nullable', 'integer', 'min:1'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:100'],
            'payment_status' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $booking = TravelBooking::where('client_identifier', $validated['client_identifier'])
            ->findOrFail($id);

        if (!empty($validated['travel_package_id'])) {
            TravelPackage::where('client_identifier', $validated['client_identifier'])
                ->findOrFail($validated['travel_package_id']);
        }

        $booking->fill($validated);

        if (!empty($validated['booking_reference']) && $validated['booking_reference'] !== $booking->booking_reference) {
            $referenceExists = TravelBooking::where('client_identifier', $validated['client_identifier'])
                ->where('booking_reference', $validated['booking_reference'])
                ->where('id', '!=', $booking->id)
                ->exists();

            if ($referenceExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A booking with this reference already exists.',
                ], 422);
            }
        }

        $booking->save();

        return response()->json([
            'status' => 'success',
            'data' => $booking->load('package:id,title'),
        ]);
    }

    /**
     * Remove the specified booking.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'client_identifier' => ['required', 'string'],
        ]);

        $booking = TravelBooking::where('client_identifier', $validated['client_identifier'])
            ->findOrFail($id);

        $booking->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Travel booking deleted successfully.',
        ]);
    }

    /**
     * Update the status of a booking.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'client_identifier' => ['required', 'string'],
            'status' => ['required', 'string', 'max:100'],
        ]);

        $booking = TravelBooking::where('client_identifier', $validated['client_identifier'])
            ->findOrFail($id);

        $booking->status = $validated['status'];
        $booking->save();

        return response()->json([
            'status' => 'success',
            'data' => $booking,
        ]);
    }

    /**
     * Update payment status for a booking.
     */
    public function updatePaymentStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'client_identifier' => ['required', 'string'],
            'payment_status' => ['required', 'string', 'max:100'],
        ]);

        $booking = TravelBooking::where('client_identifier', $validated['client_identifier'])
            ->findOrFail($id);

        $booking->payment_status = $validated['payment_status'];
        $booking->save();

        return response()->json([
            'status' => 'success',
            'data' => $booking,
        ]);
    }

    /**
     * Generate next booking reference number.
     */
    protected function generateBookingReference(TravelPackage $package): string
    {
        $prefix = strtoupper(Str::slug($package->slug, ''));
        $base = $prefix ? substr($prefix, 0, 3) : 'TRV';
        $timestamp = now()->format('YmdHis');

        return sprintf('%s-%s-%s', $base, $package->id, $timestamp);
    }
}

