<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FoodDeliveryOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'order_reference',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'customer_coordinates',
        'restaurant_id',
        'driver_id',
        'subtotal',
        'delivery_fee',
        'service_charge',
        'discount',
        'total_amount',
        'payment_method',
        'payment_status',
        'order_status',
        'special_instructions',
        'estimated_delivery_time',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'estimated_delivery_time' => 'datetime',
    ];

    /**
     * Get the restaurant for this order.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(FoodDeliveryRestaurant::class, 'restaurant_id');
    }

    /**
     * Get the driver assigned to this order.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(FoodDeliveryDriver::class, 'driver_id');
    }

    /**
     * Get all order items for this order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(FoodDeliveryOrderItem::class, 'order_id');
    }

    /**
     * Get all tracking updates for this order.
     */
    public function trackings(): HasMany
    {
        return $this->hasMany(FoodDeliveryOrderTracking::class, 'order_id');
    }

    /**
     * Get payment records for this order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(FoodDeliveryPayment::class, 'order_id');
    }

    /**
     * Scope a query to only include orders for a specific client.
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope a query to only include orders with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('order_status', $status);
    }

    /**
     * Scope a query to only include pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('order_status', 'pending');
    }

    /**
     * Scope a query to only include accepted orders.
     */
    public function scopeAccepted($query)
    {
        return $query->where('order_status', 'accepted');
    }

    /**
     * Scope a query to only include delivered orders.
     */
    public function scopeDelivered($query)
    {
        return $query->where('order_status', 'delivered');
    }

    /**
     * Generate a unique order reference.
     */
    public static function generateOrderReference(): string
    {
        $prefix = 'FD';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        
        return $prefix . $date . $random;
    }

    /**
     * Update order status and create tracking entry.
     */
    public function updateStatus(string $status, string $location = null, string $notes = null, string $updatedBy = null): void
    {
        $this->update(['order_status' => $status]);

        FoodDeliveryOrderTracking::create([
            'order_id' => $this->id,
            'status' => $status,
            'location' => $location,
            'notes' => $notes,
            'updated_by' => $updatedBy ?? 'system',
            'timestamp' => now(),
        ]);

        // Update driver status if order is delivered or cancelled
        if (in_array($status, ['delivered', 'cancelled']) && $this->driver_id) {
            $driver = $this->driver;
            if ($driver) {
                $driver->update(['status' => 'available']);
            }
        }

        // Mark driver as busy when assigned
        if ($status === 'assigned' && $this->driver_id) {
            $driver = $this->driver;
            if ($driver) {
                $driver->update(['status' => 'busy']);
            }
        }
    }

    /**
     * Check if order is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Mark order as paid.
     */
    public function markAsPaid(string $paymentReference = null): void
    {
        $this->update(['payment_status' => 'paid']);

        FoodDeliveryPayment::create([
            'order_id' => $this->id,
            'payment_method' => $this->payment_method,
            'amount' => $this->total_amount,
            'payment_reference' => $paymentReference,
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Assign driver to order.
     */
    public function assignDriver(int $driverId): void
    {
        $this->update(['driver_id' => $driverId]);
        $this->updateStatus('assigned', null, 'Driver assigned', 'system');
    }
}
