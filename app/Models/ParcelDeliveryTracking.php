<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParcelDeliveryTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'parcel_delivery_id',
        'status',
        'location',
        'notes',
        'updated_by',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    /**
     * Get the delivery that owns this tracking entry.
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(ParcelDelivery::class, 'parcel_delivery_id');
    }

    /**
     * Scope a query to order by timestamp descending.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('timestamp', 'desc');
    }

    /**
     * Scope a query to order by timestamp ascending.
     */
    public function scopeChronological($query)
    {
        return $query->orderBy('timestamp', 'asc');
    }
}

