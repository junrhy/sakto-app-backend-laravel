<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InboxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $messages = Inbox::where('client_identifier', $request->client_identifier)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'messages' => $messages,
            'unread_count' => Inbox::where('client_identifier', $request->client_identifier)
                ->where('is_read', false)
                ->count()
        ]);
    }

    /**
     * Mark a message as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $message = Inbox::where('client_identifier', $request->client_identifier)
            ->findOrFail($id);

        $message->update([
            'is_read' => true,
            'read_at' => Carbon::now()
        ]);

        return response()->json([
            'message' => 'Message marked as read successfully',
            'inbox' => $message
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(Request $request, $id)
    {
        $message = Inbox::where('client_identifier', $request->client_identifier)
            ->findOrFail($id);

        $message->delete();

        return response()->json([
            'message' => 'Message deleted successfully'
        ]);
    }

    /**
     * Create a new inbox message.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|string|in:notification,alert,message'
        ]);

        $message = Inbox::create($validated);

        return response()->json([
            'message' => 'Message created successfully',
            'inbox' => $message
        ], 201);
    }
}
