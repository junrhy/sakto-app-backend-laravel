<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactWalletTransaction extends Model
{
    protected $fillable = [
        'contact_wallet_id',
        'contact_id',
        'client_identifier',
        'type', // 'credit' or 'debit'
        'amount',
        'description',
        'reference',
        'balance_after',
        'transaction_date'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    /**
     * Get the wallet that owns the transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(ContactWallet::class, 'contact_wallet_id');
    }

    /**
     * Get the contact that owns the transaction.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the client that owns the transaction.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_identifier', 'client_identifier');
    }
} 