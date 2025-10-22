<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FnbOrder extends Model
{
    protected $fillable = [
        'client_identifier',
        'order_source',
        'table_name',
        'customer_name',
        'items',
        'item_status',
        'customer_notes',
        'discount',
        'discount_type',
        'service_charge',
        'service_charge_type',
        'subtotal',
        'total_amount',
        'status'
    ];

    protected $casts = [
        'items' => 'array',
        'item_status' => 'array',
        'discount' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];
}
