<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'borrower_name',
        'amount',
        'interest_rate',
        'start_date',
        'end_date',
        'interest_type',
        'compounding_frequency',
        'installment_frequency',
        'installment_amount',
        'status',
        'total_interest',
        'total_balance',
        'paid_amount',
        'client_identifier'
    ];
}
