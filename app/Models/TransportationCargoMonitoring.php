<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportationCargoMonitoring extends Model
{
    protected $fillable = [
        'shipment_id',
        'name',
        'quantity',
        'unit',
        'description',
        'special_handling',
        'status',
        'temperature',
        'humidity',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'temperature' => 'decimal:2',
        'humidity' => 'decimal:2',
    ];

    /**
     * Get the shipment that owns the cargo item.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(TransportationShipmentTracking::class, 'shipment_id');
    }

    /**
     * Scope a query to only include loaded cargo.
     */
    public function scopeLoaded($query)
    {
        return $query->where('status', 'Loaded');
    }

    /**
     * Scope a query to only include cargo in transit.
     */
    public function scopeInTransit($query)
    {
        return $query->where('status', 'In Transit');
    }

    /**
     * Scope a query to only include delivered cargo.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'Delivered');
    }

    /**
     * Scope a query to only include damaged cargo.
     */
    public function scopeDamaged($query)
    {
        return $query->where('status', 'Damaged');
    }

    /**
     * Scope a query to filter by name.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Scope a query to filter by description.
     */
    public function scopeByDescription($query, $description)
    {
        return $query->where('description', 'like', "%{$description}%");
    }

    /**
     * Scope a query to filter by special handling.
     */
    public function scopeBySpecialHandling($query, $specialHandling)
    {
        return $query->where('special_handling', 'like', "%{$specialHandling}%");
    }
}
