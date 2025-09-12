<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportationCargoMonitoring extends Model
{
    protected $fillable = [
        'client_identifier',
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

    protected $appends = [
        'total_unloaded_quantity',
        'remaining_quantity',
        'is_fully_unloaded',
        'is_partially_unloaded',
    ];

    /**
     * Get the shipment that owns the cargo item.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(TransportationShipmentTracking::class, 'shipment_id');
    }

    /**
     * Get the unloading records for this cargo item.
     */
    public function unloadings(): HasMany
    {
        return $this->hasMany(\App\Models\CargoUnloading::class, 'cargo_item_id');
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

    /**
     * Get the total quantity unloaded for this cargo item.
     */
    public function getTotalUnloadedQuantityAttribute()
    {
        if ($this->relationLoaded('unloadings')) {
            return $this->unloadings->sum('quantity_unloaded');
        }
        return $this->unloadings()->sum('quantity_unloaded');
    }

    /**
     * Get the remaining quantity for this cargo item.
     */
    public function getRemainingQuantityAttribute()
    {
        return max(0, $this->quantity - $this->total_unloaded_quantity);
    }

    /**
     * Check if this cargo item is fully unloaded.
     */
    public function getIsFullyUnloadedAttribute()
    {
        return $this->remaining_quantity === 0;
    }

    /**
     * Check if this cargo item is partially unloaded.
     */
    public function getIsPartiallyUnloadedAttribute()
    {
        return $this->total_unloaded_quantity > 0 && !$this->is_fully_unloaded;
    }
}
