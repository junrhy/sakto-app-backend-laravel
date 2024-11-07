<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class fnbMenuItem extends Model
{
    protected $fillable = [
        'name',
        'price',
        'category',
        'image'
    ];
}
