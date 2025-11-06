<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodDeliveryPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method',
        'amount',
        'payment_reference',
        'payment_status',
        'payment_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_data' => 'array',
    ];

    /**
     * Get the order this payment belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(FoodDeliveryOrder::class, 'order_id');
    }

    /**
     * Scope a query to only include paid payments.
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope a query to only include pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }
}
