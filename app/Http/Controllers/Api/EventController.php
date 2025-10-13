<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Services\LemonSqueezyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with('participants')->get();
        return response()->json([
            'message' => 'Events fetched successfully',
            'data' => $events
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'location' => 'required|string',
                'max_participants' => 'nullable|integer|min:1',
                'registration_deadline' => 'nullable|date|before:start_date',
                'is_public' => 'boolean',
                'is_paid_event' => 'boolean',
                'event_price' => 'nullable|numeric|min:0|required_if:is_paid_event,true',
                'currency' => 'nullable|string|size:3',
                'payment_instructions' => 'nullable|string',
                'category' => 'required|string',
                'image' => 'nullable:string',
                'status' => 'nullable|in:draft,published,archived',
                'client_identifier' => 'required|string',
                'lemon_squeezy_product_id' => 'nullable|string',
                'lemon_squeezy_variant_id' => 'nullable|string',
            ]);

            $event = Event::create($validated);

            // Log Lemon Squeezy IDs if present
            if (isset($validated['lemon_squeezy_product_id'])) {
                \Log::info('Event created with Lemon Squeezy product', [
                    'event_id' => $event->id,
                    'lemon_squeezy_product_id' => $event->lemon_squeezy_product_id,
                    'lemon_squeezy_variant_id' => $event->lemon_squeezy_variant_id,
                ]);
            }

            return response()->json([
                'message' => 'Event created successfully',
                'data' => $event
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Event creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $event = Event::with('participants')->findOrFail($id);
        return response()->json([
            'message' => 'Event fetched successfully',
            'data' => $event
        ]);
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'category' => 'sometimes|required|string',
            'image' => 'nullable:string',
            'location' => 'sometimes|required|string',
            'max_participants' => 'nullable|integer|min:1',
            'registration_deadline' => 'nullable|date|before:start_date',
            'is_public' => 'boolean',
            'is_paid_event' => 'boolean',
            'event_price' => 'nullable|numeric|min:0|required_if:is_paid_event,true',
            'currency' => 'nullable|string|size:3',
            'payment_instructions' => 'nullable|string',
            'status' => 'nullable|in:draft,published,archived',
            // Lemon Squeezy IDs - only allowed if not already set
            'lemon_squeezy_product_id' => 'nullable|string',
            'lemon_squeezy_variant_id' => 'nullable|string',
        ]);

        // Prevent overwriting existing Lemon Squeezy IDs (immutable once set)
        if ($event->lemon_squeezy_product_id) {
            unset($validated['lemon_squeezy_product_id']);
            unset($validated['lemon_squeezy_variant_id']);
            
            \Log::info('Prevented update of existing Lemon Squeezy IDs', [
                'event_id' => $event->id,
                'existing_product_id' => $event->lemon_squeezy_product_id,
            ]);
        } else {
            // Only log if we're setting IDs for the first time
            if (isset($validated['lemon_squeezy_product_id'])) {
                \Log::info('Setting Lemon Squeezy IDs for existing event', [
                    'event_id' => $event->id,
                    'lemon_squeezy_product_id' => $validated['lemon_squeezy_product_id'],
                    'lemon_squeezy_variant_id' => $validated['lemon_squeezy_variant_id'] ?? null,
                ]);
            }
        }

        $event->update($validated);

        return response()->json([
            'message' => 'Event updated successfully',
            'data' => $event
        ]);
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully'
        ]);
    }

    public function getUpcomingEvents()
    {
        $events = Event::where('start_date', '>=', Carbon::now())
            ->where('status', 'published')
            ->orderBy('start_date')
            ->with('participants')
            ->get();

        return response()->json([
            'message' => 'Upcoming events fetched successfully',
            'data' => $events
        ]);
    }

    public function getPastEvents()
    {
        $events = Event::where('end_date', '<', Carbon::now())
            ->orderBy('end_date', 'desc')
            ->with('participants')
            ->get();

        return response()->json([
            'message' => 'Past events fetched successfully',
            'data' => $events
        ]);
    }

    public function exportEvents()
    {
        $events = Event::with('participants')->get();
        
        // You can implement CSV/Excel export logic here
        // For now, returning JSON response
        return response()->json([
            'message' => 'Events exported successfully',
            'data' => $events
        ]);
    }

    public function getParticipants($id)
    {
        $event = Event::findOrFail($id);
        $participants = $event->participants;

        return response()->json([
            'message' => 'Event participants fetched successfully',
            'data' => $participants
        ]);
    }

    public function checkInParticipant(Request $request, $id, $participantId)
    {
        $event = Event::findOrFail($id);

        $participant = EventParticipant::where('event_id', $id)
            ->where('id', $participantId)
            ->firstOrFail();

        $participant->update([
            'checked_in' => true,
            'checked_in_at' => Carbon::now()
        ]);

        return response()->json([
            'message' => 'Participant checked in successfully',
            'data' => $participant
        ]);
    }

    public function registerParticipant(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string'
        ]);

        // Check if event is full
        if ($event->max_participants && $event->participants()->count() >= $event->max_participants) {
            return response()->json([
                'message' => 'Event has reached maximum participants'
            ], 400);
        }

        // Set payment status based on event type
        if ($event->is_paid_event) {
            $validated['payment_status'] = 'pending';
            $validated['amount_paid'] = 0; // Set to 0 by default, not event price
        } else {
            $validated['payment_status'] = 'paid'; // Free events are automatically marked as paid
            $validated['amount_paid'] = 0;
        }

        $participant = $event->participants()->create($validated);

        return response()->json([
            'message' => 'Participant registered successfully',
            'data' => $participant
        ], 201);
    }

    public function unregisterParticipant($id, $participantId)
    {
        $event = Event::findOrFail($id);
        $participant = EventParticipant::where('event_id', $id)
            ->where('id', $participantId)
            ->firstOrFail();

        $participant->delete();

        return response()->json([
            'message' => 'Participant unregistered successfully'
        ]);
    }

    public function updatePaymentStatus(Request $request, $id, $participantId)
    {
        $event = Event::findOrFail($id);
        $participant = EventParticipant::where('event_id', $id)
            ->where('id', $participantId)
            ->firstOrFail();

        $validated = $request->validate([
            'payment_status' => 'required|in:pending,paid,cancelled',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:255',
            'transaction_id' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string',
        ]);

        // Set payment date if status is being changed to paid
        if ($validated['payment_status'] === 'paid' && $participant->payment_status !== 'paid') {
            $validated['payment_date'] = now();
        }

        $participant->update($validated);

        return response()->json([
            'message' => 'Payment status updated successfully',
            'data' => $participant
        ]);
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:events,id'
        ]);

        Event::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'message' => 'Events deleted successfully'
        ]);
    }
}
