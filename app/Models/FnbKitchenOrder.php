<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FnbKitchenOrder extends Model
{
    protected $fillable = ['table_number', 'items', 'status', 'client_identifier'];
}
