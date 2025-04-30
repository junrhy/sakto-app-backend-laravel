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
        'status',
        'interest_type',
        'frequency',
        'total_interest',
        'total_balance',
        'paid_amount',
        'client_identifier',
        'installment_frequency',
        'installment_amount'
    ];
}
