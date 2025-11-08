<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandymanInventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'sku',
        'name',
        'type',
        'category',
        'unit',
        'quantity_on_hand',
        'quantity_available',
        'reorder_level',
        'minimum_stock',
        'requires_check_in',
        'metadata',
    ];

    protected $casts = [
        'requires_check_in' => 'boolean',
        'metadata' => 'array',
    ];

    public function transactions()
    {
        return $this->hasMany(HandymanInventoryTransaction::class, 'inventory_item_id');
    }
}

