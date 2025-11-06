<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FoodDeliveryMenuCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'name',
        'description',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    /**
     * Get all menu items in this category.
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(FoodDeliveryMenuItem::class, 'category_id');
    }

    /**
     * Scope a query to only include categories for a specific client.
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope a query to order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}
