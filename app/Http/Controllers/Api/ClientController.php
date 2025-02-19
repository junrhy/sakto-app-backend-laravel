<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    /**
     * Display a listing of the clients.
     */
    public function index()
    {
        $clients = Client::orderBy('name')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $clients
        ]);
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'contact_number' => 'nullable|string|max:20',
            'referrer' => 'required|string|max:255',
        ]);

        $validated['client_identifier'] = Str::random(10);
        $validated['active'] = true;

        $client = Client::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Client created successfully',
            'data' => $client
        ], 201);
    }

    /**
     * Display the specified client.
     */
    public function show(Client $client)
    {
        return response()->json([
            'status' => 'success',
            'data' => $client
        ]);
    }

    /**
     * Update the specified client in storage.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('clients')->ignore($client->id),
            ],
            'contact_number' => 'nullable|string|max:20',
            'referrer' => 'sometimes|required|string|max:255',
            'active' => 'sometimes|required|boolean',
        ]);

        $client->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Client updated successfully',
            'data' => $client
        ]);
    }
}
