<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class fnbSale extends Model
{
    protected $fillable = [ 'table_number', 'items', 'subtotal', 'discount', 'discount_type', 'total', 'client_identifier' ];
}
