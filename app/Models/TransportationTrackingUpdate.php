<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportationTrackingUpdate extends Model
{
    protected $fillable = [
        'client_identifier',
        'shipment_id',
        'status',
        'location',
        'timestamp',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    /**
     * Get the shipment that owns the tracking update.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(TransportationShipmentTracking::class, 'shipment_id');
    }
}
