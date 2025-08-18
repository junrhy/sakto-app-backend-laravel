<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportationMaintenanceRecord extends Model
{
    protected $fillable = [
        'truck_id',
        'date',
        'type',
        'description',
        'cost',
    ];

    protected $casts = [
        'date' => 'date',
        'cost' => 'decimal:2',
    ];

    /**
     * Get the truck that owns the maintenance record.
     */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(TransportationFleet::class, 'truck_id');
    }
}
