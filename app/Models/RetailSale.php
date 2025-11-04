<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetailSale extends Model
{
    protected $fillable = ['items', 'total_amount', 'cash_received', 'change', 'payment_method', 'client_identifier', 'discount_id', 'discount_amount'];

    protected $casts = [
        'items' => 'array',
        'total_amount' => 'decimal:2',
        'cash_received' => 'decimal:2',
        'change' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function discount()
    {
        return $this->belongsTo(RetailDiscount::class);
    }
}
