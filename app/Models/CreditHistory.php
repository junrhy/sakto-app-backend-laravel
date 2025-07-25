<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditHistory extends Model
{
    protected $fillable = [
        'credit_id', 
        'client_identifier', 
        'package_name', 
        'package_credit', 
        'package_amount', 
        'payment_method', 
        'payment_method_details', 
        'transaction_id',
        'proof_of_payment', 
        'status',
        'approved_date',
        'approved_by'
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_identifier', 'client_identifier');
    }
}
