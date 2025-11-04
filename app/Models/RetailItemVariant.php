<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetailItemVariant extends Model
{
    protected $fillable = [
        'retail_item_id',
        'client_identifier',
        'sku',
        'barcode',
        'price',
        'quantity',
        'attributes',
        'image',
        'is_active',
    ];

    protected $casts = [
        'attributes' => 'array',
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function retailItem()
    {
        return $this->belongsTo(RetailItem::class);
    }

    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

