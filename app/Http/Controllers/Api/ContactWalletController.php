<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactWallet;
use App\Models\ContactWalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ContactWalletController extends Controller
{
    /**
     * Get wallet balance for a contact
     */
    public function getBalance($contactId)
    {
        $contact = Contact::findOrFail($contactId);
        
        $wallet = ContactWallet::firstOrCreate(
            [
                'contact_id' => $contactId,
                'client_identifier' => $contact->client_identifier
            ],
            [
                'balance' => 0,
                'currency' => 'PHP',
                'status' => 'active'
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'wallet' => $wallet,
                'contact' => [
                    'id' => $contact->id,
                    'name' => $contact->first_name . ' ' . $contact->last_name,
                    'email' => $contact->email
                ]
            ]
        ]);
    }

    /**
     * Add funds to contact wallet
     */
    public function addFunds(Request $request, $contactId)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $contact = Contact::findOrFail($contactId);
            
            $wallet = ContactWallet::firstOrCreate(
                [
                    'contact_id' => $contactId,
                    'client_identifier' => $contact->client_identifier
                ],
                [
                    'balance' => 0,
                    'currency' => 'PHP',
                    'status' => 'active'
                ]
            );

            $wallet->addFunds(
                $request->amount,
                $request->description,
                $request->reference
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Funds added successfully',
                'data' => [
                    'wallet' => $wallet->fresh(),
                    'transaction' => $wallet->transactions()->latest()->first()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add funds: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deduct funds from contact wallet
     */
    public function deductFunds(Request $request, $contactId)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $contact = Contact::findOrFail($contactId);
            
            $wallet = ContactWallet::where('contact_id', $contactId)
                ->where('client_identifier', $contact->client_identifier)
                ->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found for this contact'
                ], 404);
            }

            if ($wallet->balance < $request->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient funds'
                ], 400);
            }

            $wallet->deductFunds(
                $request->amount,
                $request->description,
                $request->reference
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Funds deducted successfully',
                'data' => [
                    'wallet' => $wallet->fresh(),
                    'transaction' => $wallet->transactions()->latest()->first()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to deduct funds: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction history for a contact wallet
     */
    public function getTransactionHistory($contactId, Request $request)
    {
        $contact = Contact::findOrFail($contactId);
        
        $wallet = ContactWallet::where('contact_id', $contactId)
            ->where('client_identifier', $contact->client_identifier)
            ->first();

        if (!$wallet) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $query = $wallet->transactions();
        // Date filter: expects 'date' in YYYY-MM-DD
        if ($request->has('date')) {
            $date = $request->get('date');
            $query->whereDate('transaction_date', $date);
        }

        $transactions = $query
            ->orderBy('transaction_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Get wallet summary for all contacts of a client
     */
    public function getClientWallets(Request $request)
    {
        $clientIdentifier = $request->get('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Client identifier is required'
            ], 400);
        }

        $wallets = ContactWallet::where('client_identifier', $clientIdentifier)
            ->with(['contact:id,first_name,last_name,email'])
            ->get();

        $summary = [
            'total_contacts' => $wallets->count(),
            'total_balance' => $wallets->sum('balance'),
            'active_wallets' => $wallets->where('status', 'active')->count(),
            'wallets' => $wallets
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Transfer funds between contact wallets
     */
    public function transferFunds(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_contact_id' => 'required|exists:contacts,id',
            'to_contact_id' => 'required|exists:contacts,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->from_contact_id === $request->to_contact_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot transfer to the same contact'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $fromContact = Contact::findOrFail($request->from_contact_id);
            $toContact = Contact::findOrFail($request->to_contact_id);

            // Ensure both contacts belong to the same client
            if ($fromContact->client_identifier !== $toContact->client_identifier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot transfer between different clients'
                ], 400);
            }

            $fromWallet = ContactWallet::where('contact_id', $request->from_contact_id)
                ->where('client_identifier', $fromContact->client_identifier)
                ->first();

            if (!$fromWallet || $fromWallet->balance < $request->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient funds in source wallet'
                ], 400);
            }

            $toWallet = ContactWallet::firstOrCreate(
                [
                    'contact_id' => $request->to_contact_id,
                    'client_identifier' => $toContact->client_identifier
                ],
                [
                    'balance' => 0,
                    'currency' => 'PHP',
                    'status' => 'active'
                ]
            );

            // Deduct from source wallet
            $fromWallet->deductFunds(
                $request->amount,
                $request->description ?? 'Transfer to ' . ($toContact->sms_number ?? $toContact->first_name . ' ' . $toContact->last_name),
                $request->reference
            );

            // Add to destination wallet
            $toWallet->addFunds(
                $request->amount,
                'Transfer from ' . ($fromContact->sms_number ?? $fromContact->first_name . ' ' . $fromContact->last_name),
                $request->reference
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfer completed successfully',
                'data' => [
                    'from_wallet' => $fromWallet->fresh(),
                    'to_wallet' => $toWallet->fresh()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Transfer failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
