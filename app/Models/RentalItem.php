<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalItem extends Model
{
    protected $fillable = [
        'name',
        'category',
        'daily_rate',
        'quantity',
        'status',
        'renter_name',
        'renter_contact',
        'rental_start',
        'rental_end',
        'client_identifier',
    ];
}
