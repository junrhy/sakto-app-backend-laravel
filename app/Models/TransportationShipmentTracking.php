<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportationShipmentTracking extends Model
{
    protected $fillable = [
        'truck_id',
        'driver',
        'destination',
        'origin',
        'departure_date',
        'arrival_date',
        'status',
        'cargo',
        'weight',
        'current_location',
        'estimated_delay',
        'customer_contact',
        'priority',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'arrival_date' => 'date',
        'weight' => 'decimal:2',
        'estimated_delay' => 'integer',
    ];

    /**
     * Get the truck that owns the shipment.
     */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(TransportationFleet::class, 'truck_id');
    }

    /**
     * Get the cargo items for this shipment.
     */
    public function cargoItems(): HasMany
    {
        return $this->hasMany(TransportationCargoMonitoring::class, 'shipment_id');
    }

    /**
     * Get the tracking updates for this shipment.
     */
    public function trackingUpdates(): HasMany
    {
        return $this->hasMany(TransportationTrackingUpdate::class, 'shipment_id');
    }

    /**
     * Scope a query to only include scheduled shipments.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'Scheduled');
    }

    /**
     * Scope a query to only include shipments in transit.
     */
    public function scopeInTransit($query)
    {
        return $query->where('status', 'In Transit');
    }

    /**
     * Scope a query to only include delivered shipments.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'Delivered');
    }

    /**
     * Scope a query to only include delayed shipments.
     */
    public function scopeDelayed($query)
    {
        return $query->where('status', 'Delayed');
    }

    /**
     * Scope a query to filter by driver.
     */
    public function scopeByDriver($query, $driver)
    {
        return $query->where('driver', 'like', "%{$driver}%");
    }

    /**
     * Scope a query to filter by destination.
     */
    public function scopeByDestination($query, $destination)
    {
        return $query->where('destination', 'like', "%{$destination}%");
    }

    /**
     * Scope a query to filter by origin.
     */
    public function scopeByOrigin($query, $origin)
    {
        return $query->where('origin', 'like', "%{$origin}%");
    }

    /**
     * Scope a query to filter by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }
}
