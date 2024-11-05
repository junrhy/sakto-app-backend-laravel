<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetailSale extends Model
{
    protected $fillable = ['items', 'total_amount', 'cash_received', 'change', 'payment_method', 'client_identifier'];

    protected $casts = [
        'items' => 'array',
    ];
}
