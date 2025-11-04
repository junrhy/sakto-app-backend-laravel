<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetailItem extends Model
{
    protected $fillable = ['name', 'sku', 'images', 'quantity', 'unit', 'price', 'category_id', 'barcode', 'client_identifier', 'low_stock_threshold'];

    protected $casts = [
        'images' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(RetailCategory::class);
    }

    /**
     * Get the stock transactions for the retail item.
     */
    public function stockTransactions()
    {
        return $this->hasMany(RetailStockTransaction::class);
    }

    /**
     * Get the variants for the retail item.
     */
    public function variants()
    {
        return $this->hasMany(RetailItemVariant::class);
    }
}
