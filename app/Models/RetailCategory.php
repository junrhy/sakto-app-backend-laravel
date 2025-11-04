<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetailCategory extends Model
{
    protected $fillable = ['name', 'description', 'client_identifier'];

    public function items()
    {
        return $this->hasMany(RetailItem::class);
    }
}
