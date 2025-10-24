<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbDailyNote;
use Illuminate\Http\Request;

class FnbDailyNoteController extends Controller
{
    /**
     * Get all daily notes for a specific date
     */
    public function index(Request $request)
    {
        $request->validate([
            'client_identifier' => 'required|string',
            'note_date' => 'required|date',
        ]);

        $notes = FnbDailyNote::where('client_identifier', $request->client_identifier)
            ->where('note_date', $request->note_date)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $notes
        ]);
    }

    /**
     * Store a new daily note
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_identifier' => 'required|string',
            'note_date' => 'required|date',
            'note' => 'required|string',
            'created_by' => 'nullable|string',
        ]);

        $dailyNote = FnbDailyNote::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Daily note created successfully',
            'data' => $dailyNote
        ], 201);
    }

    /**
     * Delete a daily note
     */
    public function destroy(Request $request, $id)
    {
        $request->validate([
            'client_identifier' => 'required|string',
        ]);

        $note = FnbDailyNote::where('client_identifier', $request->client_identifier)
            ->where('id', $id)
            ->first();

        if (!$note) {
            return response()->json([
                'status' => 'error',
                'message' => 'Note not found'
            ], 404);
        }

        $note->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Daily note deleted successfully'
        ]);
    }
}

