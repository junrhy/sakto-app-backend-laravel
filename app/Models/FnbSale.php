<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FnbSale extends Model
{
    protected $fillable = [
        'table_number',
        'items',
        'subtotal',
        'discount',
        'discount_type',
        'total',
        'payment_amount',
        'payment_method',
        'change_amount',
        'client_identifier'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];
}
