<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditSpentHistory extends Model
{
    protected $fillable = [
        'credit_id',
        'client_identifier',
        'amount',
        'purpose',
        'reference_id',
        'status'
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }
}
