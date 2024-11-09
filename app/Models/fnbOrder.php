<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class fnbOrder extends Model
{
    protected $fillable = ['table_number', 'item', 'quantity', 'price', 'total', 'client_identifier'];
}
