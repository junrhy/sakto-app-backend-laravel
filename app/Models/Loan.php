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
        'compounding_frequency',
        'status',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'overpayment_balance',
        'client_identifier'
    ];
}
