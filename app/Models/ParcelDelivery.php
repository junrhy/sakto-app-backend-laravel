<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParcelDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'delivery_reference',
        'delivery_type',
        'sender_name',
        'sender_phone',
        'sender_email',
        'sender_address',
        'sender_coordinates',
        'recipient_name',
        'recipient_phone',
        'recipient_email',
        'recipient_address',
        'recipient_coordinates',
        'package_description',
        'package_weight',
        'package_length',
        'package_width',
        'package_height',
        'package_value',
        'distance_km',
        'base_rate',
        'distance_rate',
        'weight_rate',
        'size_rate',
        'delivery_type_multiplier',
        'estimated_cost',
        'final_cost',
        'pickup_date',
        'pickup_time',
        'estimated_delivery_date',
        'estimated_delivery_time',
        'actual_delivery_date',
        'actual_delivery_time',
        'courier_id',
        'courier_name',
        'courier_phone',
        'status',
        'payment_status',
        'payment_method',
        'external_service',
        'external_order_id',
        'external_tracking_url',
        'special_instructions',
        'notes',
        'pricing_breakdown',
    ];

    protected $casts = [
        'package_weight' => 'decimal:2',
        'package_length' => 'decimal:2',
        'package_width' => 'decimal:2',
        'package_height' => 'decimal:2',
        'package_value' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'base_rate' => 'decimal:2',
        'distance_rate' => 'decimal:2',
        'weight_rate' => 'decimal:2',
        'size_rate' => 'decimal:2',
        'delivery_type_multiplier' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'final_cost' => 'decimal:2',
        'pickup_date' => 'date',
        'pickup_time' => 'datetime:H:i',
        'estimated_delivery_date' => 'date',
        'estimated_delivery_time' => 'datetime:H:i',
        'actual_delivery_date' => 'date',
        'actual_delivery_time' => 'datetime:H:i',
        'pricing_breakdown' => 'array',
    ];

    /**
     * Get the courier assigned to this delivery.
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(ParcelDeliveryCourier::class, 'courier_id');
    }

    /**
     * Get all tracking updates for this delivery.
     */
    public function trackings(): HasMany
    {
        return $this->hasMany(ParcelDeliveryTracking::class, 'parcel_delivery_id');
    }

    /**
     * Scope a query to only include deliveries for a specific client.
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope a query to only include deliveries with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include pending deliveries.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include picked up deliveries.
     */
    public function scopePickedUp($query)
    {
        return $query->where('status', 'picked_up');
    }

    /**
     * Scope a query to only include in transit deliveries.
     */
    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    /**
     * Scope a query to only include delivered deliveries.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope a query to only include cancelled deliveries.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to only include deliveries by delivery type.
     */
    public function scopeByDeliveryType($query, $type)
    {
        return $query->where('delivery_type', $type);
    }

    /**
     * Generate a unique delivery reference.
     */
    public static function generateDeliveryReference(): string
    {
        do {
            $reference = 'PD' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 6));
        } while (self::where('delivery_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Calculate package volume in cubic cm.
     */
    public function getPackageVolume(): float
    {
        if (!$this->package_length || !$this->package_width || !$this->package_height) {
            return 0;
        }
        return $this->package_length * $this->package_width * $this->package_height;
    }

    /**
     * Update delivery status and create tracking entry.
     */
    public function updateStatus(string $status, string $location = null, string $notes = null, string $updatedBy = null): void
    {
        $this->update(['status' => $status]);

        // Create tracking entry
        $this->trackings()->create([
            'status' => $status,
            'location' => $location,
            'notes' => $notes,
            'updated_by' => $updatedBy ?? 'system',
            'timestamp' => now(),
        ]);

        // Update actual delivery date/time if delivered
        if ($status === 'delivered') {
            $this->update([
                'actual_delivery_date' => now()->toDateString(),
                'actual_delivery_time' => now()->toTimeString(),
            ]);
        }
    }

    /**
     * Assign courier to this delivery.
     */
    public function assignCourier($courierId): void
    {
        $courier = ParcelDeliveryCourier::find($courierId);
        if ($courier) {
            $this->update([
                'courier_id' => $courierId,
                'courier_name' => $courier->name,
                'courier_phone' => $courier->phone,
            ]);

            // Update courier status to busy
            $courier->update(['status' => 'busy']);
        }
    }

    /**
     * Check if delivery is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Mark delivery as paid.
     */
    public function markAsPaid(string $paymentMethod): void
    {
        $this->update([
            'payment_status' => 'paid',
            'payment_method' => $paymentMethod,
        ]);
    }
}

