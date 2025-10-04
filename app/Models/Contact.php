<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'date_of_birth',
        'fathers_name',
        'mothers_maiden_name',
        'email',
        'call_number',
        'sms_number',
        'whatsapp',
        'viber',
        'facebook',
        'instagram',
        'twitter',
        'linkedin',
        'address',
        'group',
        'notes',
        'id_picture',
        'id_numbers',
        'client_identifier',
    ];

    protected $casts = [
        'group' => 'array',
        'id_numbers' => 'array',
    ];

    /**
     * Get the wallet for this contact.
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(ContactWallet::class);
    }

    /**
     * Get the wallet transactions for this contact.
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(ContactWalletTransaction::class);
    }

    /**
     * Get the client that owns this contact.
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_identifier', 'client_identifier');
    }
}
