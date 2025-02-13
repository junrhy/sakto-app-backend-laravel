<?php

namespace App\Http\Controllers;

use App\Models\Inbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InboxAdminController extends Controller
{
    /**
     * Display a listing of messages.
     */
    public function index()
    {
        $messages = Inbox::orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('InboxAdmin/Index', [
            'messages' => $messages
        ]);
    }

    /**
     * Show the form for creating a new message.
     */
    public function create()
    {
        return Inertia::render('InboxAdmin/Create');
    }

    /**
     * Store a newly created message.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|string|in:notification,alert,message'
        ]);

        Inbox::create($validated);

        return redirect()->route('inbox-admin.index')
            ->with('success', 'Message sent successfully');
    }

    /**
     * Display the specified message.
     */
    public function show($id)
    {
        $message = Inbox::findOrFail($id);
        
        return Inertia::render('InboxAdmin/Show', [
            'message' => $message
        ]);
    }

    /**
     * Show the form for editing the specified message.
     */
    public function edit($id)
    {
        $message = Inbox::findOrFail($id);
        
        return Inertia::render('InboxAdmin/Edit', [
            'message' => $message
        ]);
    }

    /**
     * Update the specified message.
     */
    public function update(Request $request, $id)
    {
        $message = Inbox::findOrFail($id);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|string|in:notification,alert,message'
        ]);

        $message->update($validated);

        return redirect()->route('inbox-admin.index')
            ->with('success', 'Message updated successfully');
    }

    /**
     * Remove the specified message.
     */
    public function destroy($id)
    {
        $message = Inbox::findOrFail($id);
        $message->delete();

        return redirect()->route('inbox-admin.index')
            ->with('success', 'Message deleted successfully');
    }

    /**
     * Bulk delete messages.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:inboxes,id'
        ]);

        Inbox::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('inbox-admin.index')
            ->with('success', 'Messages deleted successfully');
    }
} 