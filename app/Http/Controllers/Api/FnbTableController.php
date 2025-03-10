<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbTable;
use Illuminate\Http\Request;

class FnbTableController extends Controller
{
    public function index(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $tables = FnbTable::where('client_identifier', $clientIdentifier)->get();
        return response()->json([
            'status' => 'success',
            'data' => [
                'fnb_tables' => $tables
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'seats' => 'required|integer|min:1',
            'status' => 'required|in:available,occupied,reserved,joined',
            'client_identifier' => 'nullable|string'
        ]);

        $table = FnbTable::create($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Table created successfully',
            'data' => [
                'fnb_table' => $table
            ]
        ], 201);
    }

    public function update(Request $request, FnbTable $fnbTable)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'seats' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:available,occupied,reserved,joined',
            'client_identifier' => 'nullable|string'
        ]);

        $fnbTable->update($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Table updated successfully',
            'data' => [
                'fnb_table' => $fnbTable
            ]
        ]);
    }

    public function destroy(FnbTable $fnbTable)
    {
        $fnbTable->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Table deleted successfully'
        ]);
    }

    public function joinTables(Request $request)
    {
        $validated = $request->validate([
            'table_ids' => 'required|array',
            'table_ids.*' => 'exists:fnb_tables,id'
        ]);

        $tables = FnbTable::whereIn('id', $validated['table_ids'])->get();

        // Check if any tables are already joined or occupied
        $invalidTables = $tables->filter(function($table) {
            return in_array($table->status, ['joined', 'occupied']);
        });

        if ($invalidTables->isNotEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some tables are already joined or occupied'
            ], 400);
        }

        // Join all tables by updating their status and joined_with field
        $joinedTableIds = implode(',', $tables->pluck('id')->toArray());
        
        foreach ($tables as $table) {
            $table->update([
                'status' => 'joined',
                'joined_with' => $joinedTableIds
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Tables joined successfully',
            'data' => $tables
        ]);
    }

    public function unjoinTables(Request $request)
    {
        $validated = $request->validate([
            'table_ids' => 'required|array',
            'table_ids.*' => 'exists:fnb_tables,id'
        ]);

        $tables = FnbTable::whereIn('id', $validated['table_ids'])->get();

        foreach ($tables as $table) {
            $table->update([
                'status' => 'available',
                'joined_with' => null
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Tables unjoined successfully',
            'data' => $tables
        ]);
    }

    public function getJoinedTables(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $joinedTables = FnbTable::where('status', 'joined')->where('client_identifier', $clientIdentifier)->get();
        return response()->json([
            'status' => 'success',
            'data' => [
                'fnb_tables' => $joinedTables
            ]
        ]);
    }

    public function getTablesOverview(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $tables = FnbTable::select('name', 'seats', 'status')->where('client_identifier', $clientIdentifier)->get();
        return response()->json($tables);
    }
}
