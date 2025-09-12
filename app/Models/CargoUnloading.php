<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CargoUnloading extends Model
{
    protected $fillable = [
        'client_identifier',
        'cargo_item_id',
        'quantity_unloaded',
        'unload_location',
        'notes',
        'unloaded_at',
        'unloaded_by',
    ];

    protected $casts = [
        'quantity_unloaded' => 'integer',
        'unloaded_at' => 'datetime',
    ];

    /**
     * Get the cargo item that owns the unloading record.
     */
    public function cargoItem(): BelongsTo
    {
        return $this->belongsTo(TransportationCargoMonitoring::class, 'cargo_item_id');
    }

    /**
     * Scope a query to filter by client identifier.
     */
    public function scopeByClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope a query to filter by cargo item.
     */
    public function scopeByCargoItem($query, $cargoItemId)
    {
        return $query->where('cargo_item_id', $cargoItemId);
    }

    /**
     * Scope a query to filter by unload location.
     */
    public function scopeByLocation($query, $location)
    {
        return $query->where('unload_location', 'like', "%{$location}%");
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('unloaded_at', [$startDate, $endDate]);
    }

    /**
     * Get the total quantity unloaded for a specific cargo item.
     */
    public static function getTotalUnloadedQuantity($cargoItemId)
    {
        return static::where('cargo_item_id', $cargoItemId)
            ->sum('quantity_unloaded');
    }

    /**
     * Get the remaining quantity for a cargo item.
     */
    public static function getRemainingQuantity($cargoItemId, $totalQuantity)
    {
        $unloadedQuantity = static::getTotalUnloadedQuantity($cargoItemId);
        return max(0, $totalQuantity - $unloadedQuantity);
    }

    /**
     * Check if a cargo item is fully unloaded.
     */
    public static function isFullyUnloaded($cargoItemId, $totalQuantity)
    {
        return static::getRemainingQuantity($cargoItemId, $totalQuantity) === 0;
    }
}
