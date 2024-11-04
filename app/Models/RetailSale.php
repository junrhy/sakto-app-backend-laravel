<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetailSale extends Model
{
    protected $fillable = ['items', 'total_amount', 'cash_received', 'change'];

    protected $casts = [
        'items' => 'array',
    ];
}
