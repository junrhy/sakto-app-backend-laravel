<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FoodDeliveryDriver extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'name',
        'phone',
        'email',
        'vehicle_type',
        'license_number',
        'status',
        'current_location',
        'current_coordinates',
        'rating',
        'total_deliveries',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'total_deliveries' => 'integer',
    ];

    /**
     * Get all orders assigned to this driver.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(FoodDeliveryOrder::class, 'driver_id');
    }

    /**
     * Scope a query to only include drivers for a specific client.
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope a query to only include available drivers.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope a query to only include busy drivers.
     */
    public function scopeBusy($query)
    {
        return $query->where('status', 'busy');
    }

    /**
     * Scope a query to only include offline drivers.
     */
    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }

    /**
     * Check if driver is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Mark driver as available.
     */
    public function markAsAvailable(): void
    {
        $this->update(['status' => 'available']);
    }

    /**
     * Mark driver as busy.
     */
    public function markAsBusy(): void
    {
        $this->update(['status' => 'busy']);
    }

    /**
     * Mark driver as offline.
     */
    public function markAsOffline(): void
    {
        $this->update(['status' => 'offline']);
    }

    /**
     * Calculate distance to given coordinates (Haversine formula).
     */
    public function distanceTo($latitude, $longitude): float
    {
        if (!$this->current_coordinates) {
            return PHP_FLOAT_MAX; // Return max distance if no coordinates
        }

        [$driverLat, $driverLng] = explode(',', $this->current_coordinates);
        $driverLat = (float) $driverLat;
        $driverLng = (float) $driverLng;

        $earthRadius = 6371; // Earth's radius in kilometers

        $latDiff = deg2rad($latitude - $driverLat);
        $lngDiff = deg2rad($longitude - $driverLng);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($driverLat)) * cos(deg2rad($latitude)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }
}
