<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = ['name', 'sku', 'images', 'quantity', 'price', 'category_id'];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class);
    }
}
