<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientDetails;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientDetailsController extends Controller
{
    public function index()
    {
        $clientDetails = ClientDetails::with('client')->orderBy('id', 'desc')->get();
        return Inertia::render('ClientDetails/Index', [
            'clientDetails' => $clientDetails
        ]);
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get(['id', 'name', 'client_identifier']);
        return Inertia::render('ClientDetails/Create', [
            'clients' => $clients
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'app_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'value' => 'required|string',
            'client_identifier' => 'required|string|max:255',
        ]);
        ClientDetails::create($validated);
        return redirect()->route('clientdetails.index')->with('success', 'Client details created successfully');
    }

    public function edit($id)
    {
        $clientDetail = ClientDetails::findOrFail($id);
        $clients = Client::orderBy('name')->get(['id', 'name', 'client_identifier']);
        return Inertia::render('ClientDetails/Edit', [
            'clientDetail' => $clientDetail,
            'clients' => $clients
        ]);
    }

    public function update(Request $request, $id)
    {
        $clientDetail = ClientDetails::findOrFail($id);
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'app_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'value' => 'required|string',
            'client_identifier' => 'required|string|max:255',
        ]);
        $clientDetail->update($validated);
        return redirect()->route('clientdetails.index')->with('success', 'Client details updated successfully');
    }

    public function destroy($id)
    {
        $clientDetail = ClientDetails::findOrFail($id);
        $clientDetail->delete();
        return redirect()->route('clientdetails.index')->with('success', 'Client details deleted successfully');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:client_details,id',
        ]);
        ClientDetails::whereIn('id', $validated['ids'])->delete();
        return redirect()->route('clientdetails.index')->with('success', 'Client details deleted successfully');
    }
} 