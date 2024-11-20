<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanBill extends Model
{
    protected $fillable = [
        'loan_id', 'amount', 'due_date', 'status', 'client_identifier'
    ];
}
