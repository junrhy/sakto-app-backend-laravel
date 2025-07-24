<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inbox;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InboxController extends Controller
{
    /**
     * Display a listing of messages.
     */
    public function index()
    {
        $messages = Inbox::leftJoin('clients', 'inboxes.client_identifier', '=', 'clients.client_identifier')
            ->select('inboxes.*', 'clients.name as client_name')
            ->orderBy('inboxes.created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Inbox/Index', [
            'messages' => $messages
        ]);
    }

    /**
     * Show the form for creating a new message.
     */
    public function create()
    {
        $clients = Client::orderBy('name')->get(['id', 'name', 'client_identifier']);

        return Inertia::render('Inbox/Create', [
            'clients' => $clients
        ]);
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

        return redirect()->route('inbox.index')
            ->with('success', 'Message sent successfully');
    }

    /**
     * Display the specified message.
     */
    public function show($id)
    {
        $message = Inbox::with('client')->findOrFail($id);
        
        return Inertia::render('Inbox/Show', [
            'message' => $message
        ]);
    }

    /**
     * Show the form for editing the specified message.
     */
    public function edit($id)
    {
        $message = Inbox::with('client')->findOrFail($id);
        
        return Inertia::render('Inbox/Edit', [
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

        return redirect()->route('inbox.index')
            ->with('success', 'Message updated successfully');
    }

    /**
     * Remove the specified message.
     */
    public function destroy($id)
    {
        $message = Inbox::findOrFail($id);
        $message->delete();

        return redirect()->route('inbox.index')
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

        return redirect()->route('inbox.index')
            ->with('success', 'Messages deleted successfully');
    }
} 