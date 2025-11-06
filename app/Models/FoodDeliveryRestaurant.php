<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FoodDeliveryRestaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'name',
        'description',
        'logo',
        'cover_image',
        'address',
        'coordinates',
        'phone',
        'email',
        'operating_hours',
        'delivery_zones',
        'status',
        'rating',
        'delivery_fee',
        'minimum_order_amount',
        'estimated_prep_time',
    ];

    protected $casts = [
        'operating_hours' => 'array',
        'delivery_zones' => 'array',
        'rating' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'estimated_prep_time' => 'integer',
    ];

    /**
     * Get all menu items for this restaurant.
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(FoodDeliveryMenuItem::class, 'restaurant_id');
    }

    /**
     * Get all orders for this restaurant.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(FoodDeliveryOrder::class, 'restaurant_id');
    }

    /**
     * Scope a query to only include restaurants for a specific client.
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope a query to only include active restaurants.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include restaurants with available menu items.
     */
    public function scopeWithAvailableItems($query)
    {
        return $query->whereHas('menuItems', function ($q) {
            $q->where('is_available', true);
        });
    }

    /**
     * Check if restaurant is currently open based on operating hours.
     */
    public function isOpen(): bool
    {
        if (!$this->operating_hours) {
            return true; // Assume always open if no hours specified
        }

        $currentDay = strtolower(now()->format('l')); // Monday, Tuesday, etc.
        $currentTime = now()->format('H:i');

        $hours = $this->operating_hours[$currentDay] ?? null;
        if (!$hours || !isset($hours['open']) || !isset($hours['close'])) {
            return true; // Assume open if no hours for this day
        }

        return $currentTime >= $hours['open'] && $currentTime <= $hours['close'];
    }

    /**
     * Check if restaurant delivers to given coordinates.
     */
    public function deliversTo($latitude, $longitude): bool
    {
        if (!$this->delivery_zones || empty($this->delivery_zones)) {
            return true; // No zones defined, assume delivers everywhere
        }

        // Simple distance check - can be enhanced with proper geospatial queries
        foreach ($this->delivery_zones as $zone) {
            if (isset($zone['coordinates'])) {
                // Basic implementation - check if coordinates are within zone
                // This can be enhanced with proper polygon/radius checking
                return true; // Simplified for now
            }
        }

        return false;
    }
}
