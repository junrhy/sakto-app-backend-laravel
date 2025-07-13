<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactWallet extends Model
{
    protected $fillable = [
        'contact_id',
        'client_identifier',
        'balance',
        'currency',
        'status',
        'last_transaction_date'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'last_transaction_date' => 'datetime',
    ];

    /**
     * Get the contact that owns the wallet.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the client that owns the wallet.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_identifier', 'client_identifier');
    }

    /**
     * Get wallet transactions.
     */
    public function transactions()
    {
        return $this->hasMany(ContactWalletTransaction::class);
    }

    /**
     * Add funds to the wallet.
     */
    public function addFunds($amount, $description = null, $reference = null)
    {
        $this->balance += $amount;
        $this->last_transaction_date = now();
        $this->save();

        // Create transaction record
        $this->transactions()->create([
            'contact_id' => $this->contact_id,
            'client_identifier' => $this->client_identifier,
            'type' => 'credit',
            'amount' => $amount,
            'description' => $description,
            'reference' => $reference,
            'balance_after' => $this->balance,
        ]);

        return $this;
    }

    /**
     * Deduct funds from the wallet.
     */
    public function deductFunds($amount, $description = null, $reference = null)
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient funds');
        }

        $this->balance -= $amount;
        $this->last_transaction_date = now();
        $this->save();

        // Create transaction record
        $this->transactions()->create([
            'contact_id' => $this->contact_id,
            'client_identifier' => $this->client_identifier,
            'type' => 'debit',
            'amount' => $amount,
            'description' => $description,
            'reference' => $reference,
            'balance_after' => $this->balance,
        ]);

        return $this;
    }
}