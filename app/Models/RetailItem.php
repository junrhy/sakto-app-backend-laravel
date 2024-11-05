<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetailItem extends Model
{
    protected $fillable = ['name', 'sku', 'images', 'quantity', 'unit', 'price', 'category_id', 'barcode', 'client_identifier'];

    protected $casts = [
        'images' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(RetailCategory::class);
    }
}
