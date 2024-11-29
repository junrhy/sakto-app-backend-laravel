<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalPropertyPayment extends Model
{
    protected $fillable = [
        'rental_property_id',
        'amount',
        'payment_date',
        'reference',
        'client_identifier'
    ];
}
