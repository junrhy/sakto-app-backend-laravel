<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FnbMenuItem extends Model
{
    protected $fillable = ['name', 'price', 'category', 'image', 'is_available_personal', 'is_available_online', 'client_identifier', 'delivery_fee'];
}
