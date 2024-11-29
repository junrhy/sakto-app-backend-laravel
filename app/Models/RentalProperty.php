<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalProperty extends Model
{
    protected $fillable = [
        'address', 
        'type', 
        'bedrooms', 
        'bathrooms', 
        'rent', 
        'status', 
        'tenant_name', 
        'lease_start', 
        'lease_end', 
        'client_identifier'
    ];
}
