<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'client_identifier',
        'email',
        'contact_number',
        'referrer',
        'active'
    ];

    /**
     * Get the credit record for this client.
     */
    public function credit()
    {
        return $this->hasOne(Credit::class, 'client_identifier', 'client_identifier');
    }

    /**
     * Get the credit histories for this client.
     */
    public function creditHistories()
    {
        return $this->hasMany(CreditHistory::class, 'client_identifier', 'client_identifier');
    }

    /**
     * Get the credit spent histories for this client.
     */
    public function creditSpentHistories()
    {
        return $this->hasMany(CreditSpentHistory::class, 'client_identifier', 'client_identifier');
    }
}
