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
    ];

    protected $casts = [
        'last_maintenance' => 'date',
        'fuel_level' => 'decimal:2',
        'capacity' => 'integer',
        'mileage' => 'integer',
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
}
