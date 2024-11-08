<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FnbTable;
use Illuminate\Http\Request;

class FnbTableController extends Controller
{
    public function index()
    {
        $tables = FnbTable::all();
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
}
