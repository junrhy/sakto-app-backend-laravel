<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodDeliveryOrder extends Model
{
    protected $fillable = [
        'app_name',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_address',
        'customer_email',
        'items',
        'total_amount',
        'delivery_fee',
        'discount',
        'tax',
        'grand_total',
        'special_instructions',
        'order_status',
        'order_payment_method',
        'order_payment_status',
        'order_payment_reference'
    ];

    protected $casts = [
        'items' => 'array',
    ];
}
