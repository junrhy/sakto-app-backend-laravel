<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetailCategory extends Model
{
    protected $fillable = ['name'];

    public function items()
    {
        return $this->hasMany(RetailItem::class);
    }
}
