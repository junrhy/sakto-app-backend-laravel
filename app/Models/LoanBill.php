<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanBill extends Model
{
    protected $fillable = [
        'loan_id',
        'total_amount',
        'total_amount_due',
        'due_date',
        'status',
        'client_identifier',
        'principal',
        'interest',
        'installment_amount',
        'bill_number',
        'note',
        'penalty_amount'
    ];
}
