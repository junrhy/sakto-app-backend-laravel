<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->get('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        $contacts = Contact::where('client_identifier', $clientIdentifier)
            ->with(['wallet' => function($query) {
                $query->select('id', 'contact_id', 'balance', 'currency', 'status');
            }])
            ->get()
            ->map(function($contact) {
                $contactData = $contact->toArray();
                $contactData['wallet_balance'] = $contact->wallet ? $contact->wallet->balance : 0;
                $contactData['wallet_currency'] = $contact->wallet ? $contact->wallet->currency : 'PHP';
                $contactData['wallet_status'] = $contact->wallet ? $contact->wallet->status : 'active';
                return $contactData;
            });

        return response()->json([
            'success' => true,
            'data' => $contacts
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'gender' => 'required|string|in:male,female,other',
                'date_of_birth' => 'nullable|string|max:255',
                'fathers_name' => 'nullable|string|max:255',
                'mothers_maiden_name' => 'nullable|string|max:255',
                'email' => 'required|email|max:255',
                'call_number' => 'nullable|string|max:20',
                'sms_number' => 'nullable|string|max:20',
                'whatsapp' => 'nullable|string|max:20',
                'facebook' => 'nullable|string|max:255|url',
                'instagram' => 'nullable|string|max:255|url',
                'twitter' => 'nullable|string|max:255|url',
                'linkedin' => 'nullable|string|max:255|url',
                'address' => 'nullable|string|max:500',
                'group' => 'nullable|array',
                'notes' => 'nullable|string',
                'id_picture' => 'nullable|string',
                'client_identifier' => 'required|string|max:255',
            ]);

            $contact = Contact::create($validated);
            return response()->json($contact, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $contact = Contact::find($id);
        return response()->json($contact);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|string|in:male,female,other',
            'date_of_birth' => 'nullable|string|max:255',
            'fathers_name' => 'nullable|string|max:255',
            'mothers_maiden_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'call_number' => 'nullable|string|max:20',
            'sms_number' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'facebook' => 'nullable|string|max:255|url',
            'instagram' => 'nullable|string|max:255|url',
            'twitter' => 'nullable|string|max:255|url',
            'linkedin' => 'nullable|string|max:255|url',
            'address' => 'nullable|string|max:500',
            'group' => 'nullable|array',
            'notes' => 'nullable|string',
            'id_picture' => 'nullable|string'
        ]);

        $contact = Contact::find($id);
        $contact->update($validated);
        return response()->json($contact, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $contact = Contact::find($id);
        $contact->delete();
        return response()->json(['message' => 'Contact deleted successfully'], 204);
    }

    public function bulkDestroy(Request $request)
    {
        Contact::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Contacts deleted successfully'], 204);
    }
}
