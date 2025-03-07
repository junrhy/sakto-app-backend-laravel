<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FnbOrder extends Model
{
    protected $fillable = ['table_number', 'item', 'quantity', 'price', 'total', 'client_identifier', 'status'];
}
