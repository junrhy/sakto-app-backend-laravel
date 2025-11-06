<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodDeliveryOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'menu_item_id',
        'item_name',
        'item_price',
        'quantity',
        'subtotal',
        'special_instructions',
    ];

    protected $casts = [
        'item_price' => 'decimal:2',
        'quantity' => 'integer',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Get the order this item belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(FoodDeliveryOrder::class, 'order_id');
    }

    /**
     * Get the menu item this order item is based on.
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(FoodDeliveryMenuItem::class, 'menu_item_id');
    }
}
