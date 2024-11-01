<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    protected $fillable = ['name'];

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }
}
