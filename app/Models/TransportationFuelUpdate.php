<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportationFuelUpdate extends Model
{
    protected $fillable = [
        'truck_id',
        'timestamp',
        'previous_level',
        'new_level',
        'liters_added',
        'cost',
        'location',
        'updated_by',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'previous_level' => 'decimal:2',
        'new_level' => 'decimal:2',
        'liters_added' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    /**
     * Get the truck that owns the fuel update.
     */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(TransportationFleet::class, 'truck_id');
    }
}
