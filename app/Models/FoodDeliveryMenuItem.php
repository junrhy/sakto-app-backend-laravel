<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FoodDeliveryMenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'restaurant_id',
        'category_id',
        'name',
        'description',
        'image',
        'price',
        'discount_price',
        'is_available',
        'is_featured',
        'preparation_time',
        'dietary_info',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'preparation_time' => 'integer',
        'dietary_info' => 'array',
    ];

    /**
     * Get the restaurant that owns this menu item.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(FoodDeliveryRestaurant::class, 'restaurant_id');
    }

    /**
     * Get the category this menu item belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FoodDeliveryMenuCategory::class, 'category_id');
    }

    /**
     * Get all order items for this menu item.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(FoodDeliveryOrderItem::class, 'menu_item_id');
    }

    /**
     * Scope a query to only include items for a specific client.
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope a query to only include available items.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope a query to only include featured items.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Get the effective price (discount price if available, otherwise regular price).
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->discount_price ?? $this->price;
    }

    /**
     * Check if item is on discount.
     */
    public function isOnDiscount(): bool
    {
        return $this->discount_price !== null && $this->discount_price < $this->price;
    }
}
