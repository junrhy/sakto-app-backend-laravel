<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodDeliveryOrderTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
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
     * Get the order this tracking belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(FoodDeliveryOrder::class, 'order_id');
    }
}
