<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransportationFleet extends Model
{
    protected $fillable = [
        'client_identifier',
        'plate_number',
        'model',
        'capacity',
        'status',
        'last_maintenance',
        'fuel_level',
        'mileage',
        'driver',
        'driver_contact',
        'current_latitude',
        'current_longitude',
        'last_location_update',
        'current_address',
        'speed',
        'heading',
    ];

    protected $casts = [
        'last_maintenance' => 'date',
        'fuel_level' => 'decimal:2',
        'capacity' => 'integer',
        'mileage' => 'integer',
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
        'last_location_update' => 'datetime',
        'speed' => 'decimal:2',
        'heading' => 'decimal:2',
    ];

    /**
     * Get the shipments for this truck.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(TransportationShipmentTracking::class, 'truck_id');
    }

    /**
     * Get the fuel updates for this truck.
     */
    public function fuelUpdates(): HasMany
    {
        return $this->hasMany(TransportationFuelUpdate::class, 'truck_id');
    }

    /**
     * Get the maintenance records for this truck.
     */
    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(TransportationMaintenanceRecord::class, 'truck_id');
    }

    /**
     * Get the bookings for this truck.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(TransportationBooking::class, 'truck_id');
    }

    /**
     * Scope a query to only include available trucks.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'Available');
    }

    /**
     * Scope a query to only include trucks in transit.
     */
    public function scopeInTransit($query)
    {
        return $query->where('status', 'In Transit');
    }

    /**
     * Scope a query to only include trucks in maintenance.
     */
    public function scopeInMaintenance($query)
    {
        return $query->where('status', 'Maintenance');
    }

    /**
     * Scope a query to filter by driver.
     */
    public function scopeByDriver($query, $driver)
    {
        return $query->where('driver', 'like', "%{$driver}%");
    }

    /**
     * Scope a query to filter by plate number.
     */
    public function scopeByPlateNumber($query, $plateNumber)
    {
        return $query->where('plate_number', 'like', "%{$plateNumber}%");
    }

    /**
     * Scope a query to filter by model.
     */
    public function scopeByModel($query, $model)
    {
        return $query->where('model', 'like', "%{$model}%");
    }

    /**
     * Scope a query to only include trucks with GPS location data.
     */
    public function scopeWithLocation($query)
    {
        return $query->whereNotNull('current_latitude')
                    ->whereNotNull('current_longitude');
    }

    /**
     * Scope a query to only include trucks with recent location updates.
     */
    public function scopeWithRecentLocation($query, $minutes = 30)
    {
        return $query->where('last_location_update', '>=', now()->subMinutes($minutes));
    }

    /**
     * Check if truck has valid GPS location data.
     */
    public function hasValidLocation(): bool
    {
        return !is_null($this->current_latitude) && 
               !is_null($this->current_longitude) && 
               !is_null($this->last_location_update);
    }

    /**
     * Check if truck location data is recent (within specified minutes).
     */
    public function hasRecentLocation(int $minutes = 30): bool
    {
        return $this->hasValidLocation() && 
               $this->last_location_update->isAfter(now()->subMinutes($minutes));
    }

    /**
     * Get formatted location string.
     */
    public function getFormattedLocationAttribute(): string
    {
        if (!$this->hasValidLocation()) {
            return 'Location not available';
        }

        return "{$this->current_latitude}, {$this->current_longitude}";
    }

    /**
     * Calculate distance to another location (in kilometers).
     */
    public function distanceTo(float $latitude, float $longitude): float
    {
        if (!$this->hasValidLocation()) {
            return 0;
        }

        $earthRadius = 6371; // Earth's radius in kilometers

        $latFrom = deg2rad($this->current_latitude);
        $lonFrom = deg2rad($this->current_longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
